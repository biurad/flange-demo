<?php declare(strict_types=1);

/*
 * This file is part of Flange Blog Demo Project
 *
 * @copyright 2022 Divine Niiquaye Ibok (https://divinenii.com/)
 * @license   https://opensource.org/licenses/MIT License
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\EventListener;

use Biurad\Http\Request;
use Biurad\Http\Response\{HtmlResponse, RedirectResponse};
use Biurad\Security\Handler\FirewallAccessHandler;
use Flange\Event\{ControllerEvent, ExceptionEvent, RequestEvent, ResponseEvent};
use Flange\Events;
use Flight\Routing\Router;
use Nette\Utils\Callback;
use Psr\Http\Server\RequestHandlerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Exception\{AccessDeniedException, UnsupportedUserException, UserNotFoundException};
use Symfony\Component\Security\Core\User\{EquatableInterface, UserProviderInterface};

/**
 * @author Divine Niiquaye Ibok <divineibok@gmail.com>
 */
class AppEventListener implements EventSubscriberInterface
{
    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            Events::REQUEST => [['onRequest'], ['refreshToken']],
            Events::CONTROLLER => 'onController',
            // Events::RESPONSE => 'onResponse',
            Events::EXCEPTION => 'onException',
        ];
    }

    public function onRequest(RequestEvent $event): void
    {
        $container = $event->getApplication();

        /** @var FirewallAccessHandler $fireWallHandler */
        $fireWallHandler = $container->get('security.access_map_handler');

        try {
            $isGranted = $fireWallHandler->authenticate($event->getRequest());
        } catch (AccessDeniedException $e) {
            $isGranted = false;
        }

        if (!$isGranted) {
            // if you want to support redirecting to support locale
            // Instead of authenticating a request event, authenticate a controller event
            $event->setResponse(new RedirectResponse((string) $container->getRouter()->generateUri('security_login')));
        }
    }

    public function refreshToken(RequestEvent $event): void
    {
        $container = $event->getApplication();

        /** @var TokenStorageInterface $tokenStorage */
        $tokenStorage = $container->get('security.token_storage');

        if (null !== $token = $tokenStorage->getToken()) {
            /** @var UserProviderInterface $provider */
            $provider = $container->get('security.user_providers');
            $user = $token->getUser();

            if (!$provider->supportsClass($user::class)) {
                return;
            }

            try {
                $refreshedUser = $provider->refreshUser($user); // Refresh the existing user

                if ($user instanceof EquatableInterface && !$user->isEqualTo($refreshedUser)) {
                    return;
                }
            } catch (UnsupportedUserException|UserNotFoundException) {
                return;
            }

            $newToken = clone $token;
            $newToken->setUser($refreshedUser);
            $tokenStorage->setToken($newToken);
        }
    }

    public function onController(ControllerEvent $event): void
    {
        $container = $event->getApplication();
        $request = $event->getRequest();

        if (null !== $route = $request->getAttribute(Router::class)) {
            $routeArguments = $route['arguments'] ?? [];
            $routeHandler = $route['handler'] ?? null;

            if (isset($routeArguments['_locale'])) {
                $container->get('translator')->setLocale($routeArguments['_locale']);

                if ($request instanceof Request) {
                    $request->getRequest()->setLocale($routeArguments['_locale']);
                }
            }

            try {
                $routeRef = Callback::toReflection($routeHandler);
            } catch (\ReflectionException $e) {
                if (\is_string($routeHandler)) {
                    if (\is_subclass_of($routeHandler, RequestHandlerInterface::class)) {
                        $routeRef = new \ReflectionMethod($routeHandler, 'handle');
                    } elseif (\method_exists($routeHandler, '__invoke')) {
                        $routeRef = new \ReflectionMethod($routeHandler, '__invoke');
                    }
                }
            }
        }

        $container->get('templating')->globals += [
            '_route' => $route['name'] ?? null,
            '_route_params' => $routeArguments,
            '_route_reflection' => $routeRef ?? null,
        ];
    }

    public function onResponse(ResponseEvent $event): void
    {
    }

    public function onException(ExceptionEvent $event): void
    {
        $container = $event->getApplication();

        if ($container->isDebug()) {
            return;
        }

        $code = $event->getThrowable()->getCode();
        $response = $container->get('templating')->renderTemplates([
            'errors/error'.$code.'.html.twig', // error.xxx.html.twig format ...
            'errors/error.html.twig',
        ], []);

        if (null !== $response) {
            $event->setResponse(new HtmlResponse($response, $code ?: 500));
        }
    }
}
