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

use App\Form\Type\ChangePasswordType;
use App\Form\UserType;
use Biurad\Http\Response\{HtmlResponse, RedirectResponse};
use Biurad\Http\ServerRequest;
use Biurad\UI\Template;
use Doctrine\ORM\EntityManagerInterface;
use Flight\Routing\Annotation\Route;
use Flight\Routing\Interfaces\UrlGeneratorInterface;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;

/**
 * Controller used to manage current user.
 *
 * @author Divine Niiquaye Ibok <divineibok@gmail.com>
 */
#[Route('/profile')]
class UserController
{
    public function __construct(private Template $template)
    {
    }

    #[Route('/edit', 'user_edit', ['GET', 'POST'])]
    public function edit(
        ?string $_locale,
        ServerRequest $request,
        TokenStorageInterface $tokenStorage,
        UrlGeneratorInterface $router,
        FormFactoryInterface $formFactory,
        EntityManagerInterface $entityManager
    ): ResponseInterface {
        $form = $formFactory->create(UserType::class, $user = $tokenStorage->getToken()->getUser());
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            $request->getRequest()->getSession()->getFlashBag()->add('success', 'user.updated_successfully');

            return new RedirectResponse((string) $router->generateUri('user_edit', \compact('_locale')));
        }

        return new HtmlResponse(
            $this->template->render('user/edit.html.twig', [
                'user' => $user,
                'form' => $form->createView(),
            ])
        );
    }

    #[Route('/change-password', methods: ['GET', 'POST'], name: 'user_change_password')]
    public function changePassword(
        ?string $_locale,
        ServerRequest $request,
        TokenStorageInterface $tokenStorage,
        UrlGeneratorInterface $router,
        FormFactoryInterface $formFactory,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $entityManager
    ): ResponseInterface {
        $form = $formFactory->create(ChangePasswordType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $user = $tokenStorage->getToken()->getUser();

            if (!$user instanceof PasswordAuthenticatedUserInterface) {
                throw new \LogicException('The user must implement the PasswordAuthenticatedUserInterface.');
            }

            $user->setPassword($passwordHasher->hashPassword($user, $form->get('newPassword')->getData()));
            $entityManager->flush();

            return new RedirectResponse((string) $router->generateUri('security_logout', \compact('_locale')));
        }

        return new HtmlResponse($this->template->render('user/change_password.html.twig', ['form' => $form->createView()]));
    }
}
