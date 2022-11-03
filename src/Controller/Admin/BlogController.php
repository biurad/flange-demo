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

namespace App\Controller\Admin;

use App\Entity\Post;
use App\Form\PostType;
use App\Repository\PostRepository;
use App\Security\PostVoter;
use Biurad\Http\Response\{HtmlResponse, RedirectResponse};
use Biurad\Http\ServerRequest;
use Biurad\Security\{Authenticator, Helper};
use Biurad\UI\Template;
use Doctrine\ORM\EntityManagerInterface;
use Flight\Routing\Annotation\Route;
use Flight\Routing\Interfaces\UrlGeneratorInterface;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Exception\SessionNotFoundException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Csrf\{CsrfToken, CsrfTokenManagerInterface};

/**
 * Controller used to manage blog contents in the backend.
 *
 * Please note that the application backend is developed manually for learning
 * purposes.
 *
 * @author Divine Niiquaye Ibok <divineibok@gmail.com>
 */
#[Route('/admin/post')]
class BlogController
{
    public function __construct(private Template $template, private Authenticator $authenticator)
    {
    }

    /**
     * Lists all Post entities.
     *
     * This controller responds to two different routes with the same URL:
     *   * 'admin_post_index' is the route with a name that follows the same
     *     structure as the rest of the controllers of this class.
     *   * 'admin_index' is a nice shortcut to the backend homepage. This allows
     *     to create simpler links in the templates. Moreover, in the future we
     *     could move this annotation to any other controller while maintaining
     *     the route name and therefore, without breaking any existing link.
     */
    #[Route('/', 'admin_index', ['GET']), Route('/', 'admin_post_index', ['GET'])]
    public function index(PostRepository $posts): ResponseInterface
    {
        $authorPosts = $posts->findBy(['author' => $this->authenticator->getUser()], ['publishedAt' => 'DESC']);

        return new HtmlResponse($this->template->render('admin/blog/index.html.twig', ['posts' => $authorPosts]));
    }

    /**
     * Creates a new Post entity.
     */
    #[Route('/new/', 'admin_post_new', ['GET', 'POST'])]
    public function new(
        ?string $_locale,
        ServerRequest $request,
        UrlGeneratorInterface $router,
        FormFactoryInterface $formFactory,
        EntityManagerInterface $entityManager
    ): ResponseInterface {
        $post = new Post();
        $post->setAuthor($this->authenticator->getUser());

        // See https://symfony.com/doc/current/form/multiple_buttons.html
        $form = $formFactory->create(PostType::class, $post)->add('saveAndCreateNew', SubmitType::class);
        $form->handleRequest($request);

        // the isSubmitted() method is completely optional because the other
        // isValid() method already checks whether the form is submitted.
        // However, we explicitly add it to improve code readability.
        // See https://symfony.com/doc/current/forms.html#processing-forms
        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($post);
            $entityManager->flush();

            // Flash messages are used to notify the user about the result of the
            // actions. They are deleted automatically from the session as soon
            // as they are accessed.
            $this->addFlash($request, 'success', 'post.created_successfully');

            if ($form->get('saveAndCreateNew')->isClicked()) {
                $postNew = (string) $router->generateUri('admin_post_new');
            }

            return new RedirectResponse($postNew ?? (string) $router->generateUri('admin_post_index', \compact('_locale')));
        }

        return new HtmlResponse(
            $this->template->render('admin/blog/new.html.twig', [
                'post' => $post,
                'form' => $form->createView(),
            ])
        );
    }

    /**
     * Finds and displays a Post entity.
     */
    #[Route('/{id:\d+}/', 'admin_post_show', ['GET'])]
    public function show(int $id, PostRepository $posts): ResponseInterface
    {
        $post = $posts->findOneBy(['id' => $id]);
        $this->denyAccessUnlessGranted(PostVoter::SHOW, $post, 'Posts can only be shown to their authors.');

        return new HtmlResponse(
            $this->template->render('admin/blog/show.html.twig', [
                'post' => $post,
            ])
        );
    }

    /**
     * Displays a form to edit an existing Post entity.
     */
    #[Route('/{id:\d+}/edit/', 'admin_post_edit', ['GET', 'POST'])]
    public function edit(
        int $id,
        ?string $_locale,
        ServerRequest $request,
        UrlGeneratorInterface $router,
        FormFactoryInterface $formFactory,
        EntityManagerInterface $entityManager
    ): ResponseInterface {
        $post = $entityManager->find(Post::class, $id);
        $this->denyAccessUnlessGranted(PostVoter::EDIT, $post, 'Posts can only be edited by their authors.');

        $form = $formFactory->create(PostType::class, $post);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            $this->addFlash($request, 'success', 'post.updated_successfully');

            return new RedirectResponse((string) $router->generateUri('admin_post_edit', \compact('id', '_locale')));
        }

        return new HtmlResponse(
            $this->template->render('admin/blog/edit.html.twig', [
                'post' => $post,
                'form' => $form->createView(),
            ])
        );
    }

    /**
     * Deletes a Post entity.
     */
    #[Route('/{id}/delete/', 'admin_post_delete', ['POST'])]
    public function delete(
        int $id,
        ?string $_locale,
        ServerRequest $request,
        CsrfTokenManagerInterface $csrf,
        UrlGeneratorInterface $router,
        EntityManagerInterface $entityManager
    ): ResponseInterface {
        $token = new CsrfToken('delete', Helper::getParameterValue($request, 'token'));

        if ($csrf->isTokenValid($token)) {
            $post = $entityManager->find(Post::class, $id);
            $this->denyAccessUnlessGranted(PostVoter::DELETE, $post);

            // Delete the tags associated with this blog post. This is done automatically
            // by Doctrine, except for SQLite (the database used in this application)
            // because foreign key support is not enabled by default in SQLite
            $post->getTags()->clear();

            $entityManager->remove($post);
            $entityManager->flush();

            $this->addFlash($request, 'success', 'post.deleted_successfully');
        }

        return new RedirectResponse((string) $router->generateUri('admin_post_index', \compact('_locale')));
    }

    /**
     * Throws an exception unless the attribute is granted against the current authentication token and optionally
     * supplied subject.
     *
     * @throws AccessDeniedException
     */
    protected function denyAccessUnlessGranted(mixed $attribute, mixed $subject = null, string $message = 'Access Denied.'): void
    {
        if (!$this->authenticator->isGranted($attribute, $subject)) {
            $exception = new AccessDeniedException($message);
            $exception->setAttributes($attribute);
            $exception->setSubject($subject);

            throw $exception;
        }
    }

    /**
     * Adds a flash message to the current session for type.
     *
     * @throws \LogicException
     */
    protected function addFlash(ServerRequest $request, string $type, mixed $message): void
    {
        try {
            $request->getRequest()->getSession()->getFlashBag()->add($type, $message);
        } catch (SessionNotFoundException $e) {
            throw new \LogicException('You cannot use the addFlash method if sessions are disabled. Enable them in "resources/bootstrap.php" file.', 0, $e);
        }
    }
}
