<?php declare(strict_types=1);

/*
 * This file is part of RadePHP Demo Project
 *
 * @copyright 2022 Divine Niiquaye Ibok (https://divinenii.com/)
 * @license   https://opensource.org/licenses/MIT License
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Controller;

use Biurad\Security\{Authenticator, Helper};
use Biurad\Http\Response\{HtmlResponse, RedirectResponse};
use Biurad\Http\ServerRequest;
use Biurad\Security\Handler\LogoutHandler;
use Biurad\UI\Template;
use Flight\Routing\Annotation\Route;
use Flight\Routing\Interfaces\UrlGeneratorInterface;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Security;

/**
 * Controller used to manage the application security.
 *
 * @author Divine Niiquaye Ibok <divineibok@gmail.com>
 */
class SecurityController
{
    public function __construct(private Template $template)
    {
    }

    #[Route('/login/', 'security_login', ['GET', 'POST'])]
    public function login(
        ?string $_locale,
        ServerRequest $request,
        UrlGeneratorInterface $router,
        Authenticator $authenticator
    ): ResponseInterface {
        if (null === $authenticator->getToken()) {
            $session = ($req = $request->getRequest())->getSession();

            try {
                $authenticated = $authenticator->authenticate($request, ['_identifier', '_password', '_csrf_token']);

                if ($authenticated instanceof ResponseInterface) {
                    return $authenticated;
                }
            } catch (AuthenticationException $e) {
            }

            if (isset($e) || null === $authenticator->getToken()) {
                return new HtmlResponse($this->template->render('security/login.html.twig', [
                    'last_username' => $session->get(Security::LAST_USERNAME),
                    'error' => $session->get(Security::AUTHENTICATION_ERROR, $e ?? null),
                ]));
            }

            $session->set('_target_path', $req->get('_target_path') ?: null); // redirects to the original target URL
            $session->set(Security::LAST_USERNAME, $req->get('_identifier')); // saves last username
        }

        return new RedirectResponse(Helper::determineTargetUrl($request) ?? (string) $router->generateUri('blog_index', \compact('_locale')));
    }

    /**
     * This is the route the user can use to logout.
     */
    #[Route('/logout/', 'security_logout', 'GET')]
    public function logout(
        ?string $_locale,
        ServerRequest $request,
        UrlGeneratorInterface $router,
        LogoutHandler $logout
    ): ResponseInterface {
        $logout->handle($request);

        return new RedirectResponse((string) $router->generateUri('blog_index', \compact('_locale')));
    }
}
