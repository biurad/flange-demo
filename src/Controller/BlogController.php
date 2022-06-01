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

use App\Event\CommentCreatedEvent;
use App\Form\CommentType;
use App\Entity\{Comment, Post};
use App\Repository\{PostRepository, TagRepository};
use Biurad\Http\Response\{HtmlResponse, JsonResponse, RedirectResponse, XmlResponse};
use Biurad\Http\ServerRequest;
use Biurad\UI\Template;
use Doctrine\ORM\EntityManagerInterface;
use Flight\Routing\Annotation\Route;
use Flight\Routing\Exceptions\RouteNotFoundException;
use Flight\Routing\Interfaces\UrlGeneratorInterface;
use Flight\Routing\Router;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * Controller used to manage blog contents in the public part of the site.
 *
 * @author Divine Niiquaye Ibok <divineibok@gmail.com>
 */
#[Route('/blog')]
class BlogController
{
    public function __construct(private Template $template)
    {
    }

    #[
        Route('/', 'blog_index', ['GET'], attributes: ['page' => '1', '_format' => 'html']),
        Route('/rss.xml', 'blog_rss', ['GET'], attributes: ['page' => '1', '_format' => 'xml']),
        Route('/page/{page:[1-9]\d*}', 'blog_index_paginated', ['GET'], attributes: ['_format' => 'html']),
    ]
    public function index(ServerRequest $request, PostRepository $posts, TagRepository $tags, int $page, string $_format): ResponseInterface
    {
        $request = $request->getRequest();

        if ($tag = $request->query->get('tag')) {
            $tag = $tags->findOneBy(['name' => $tagName = $request->query->get('tag')]);
        }

        $template = $this->template->render('blog/index.' . $_format . '.twig', [
            'paginator' => $posts->findLatest($page, $tag),
            'tagName' => $tagName ?? null,
        ]);

        return 'html' === $_format ? new HtmlResponse($template) : new XmlResponse($template);
    }

    #[Route('/posts/{slug:slug}/', 'blog_post', ['GET'])]
    public function postShow(string $slug, PostRepository $posts, FormFactoryInterface $formFactory): ResponseInterface
    {
        $post = $posts->findOneBy(['slug' => $slug]);

        if (null === $post) {
            throw new RouteNotFoundException('No posts found', HtmlResponse::STATUS_NOT_FOUND);
        }
        // Symfony's 'dump()' function is an improved version of PHP's 'var_dump()' but
        // it's not available in the 'prod' environment to prevent leaking sensitive information.
        // It can be used both in PHP files and Twig templates, but it requires to
        // have enabled the DebugBundle. Uncomment the following line to see it in action:
        //
        // See https://symfony.com/doc/current/templates.html#the-dump-twig-utilities
        //
        // You can also leverage Symfony's 'dd()' function that dumps and
        // stops the execution
        return new HtmlResponse(
            $this->template->render('blog/post_show.html.twig', [
                'post' => $post,
                'form' => $formFactory->create(CommentType::class)->createView(),
            ])
        );
    }

    #[Route('/search/', 'blog_search', ['GET'])]
    public function search(?string $_locale, ServerRequest $request, Router $router, PostRepository $posts): ResponseInterface
    {
        $request = $request->getRequest();
        $query = $request->query->get('q', '');
        $limit = $request->query->get('l', 10);

        if (!$request->isXmlHttpRequest()) {
            return new HtmlResponse($this->template->render('blog/search.html.twig', ['query' => $query]));
        }

        $foundPosts = $posts->findBySearchQuery($query, $limit);
        $results = [];

        foreach ($foundPosts as $post) {
            $results[] = [
                'title' => \htmlspecialchars($post->getTitle(), \ENT_COMPAT | \ENT_HTML5),
                'date' => $post->getPublishedAt()->format('M d, Y'),
                'author' => \htmlspecialchars($post->getAuthor()->getFullName(), \ENT_COMPAT | \ENT_HTML5),
                'summary' => \htmlspecialchars($post->getSummary(), \ENT_COMPAT | \ENT_HTML5),
                'url' => (string) $router->generateUri('blog_post', ['slug' => $post->getSlug(), '_locale' => $_locale]),
            ];
        }

        return new JsonResponse($results);
    }

    #[Route('/comment/{postSlug:slug}/new/', methods: ['POST'], name: 'comment_new')]
    public function commentNew(
        ?string $_locale,
        string $postSlug,
        ServerRequest $request,
        TokenStorageInterface $tokenStorage,
        FormFactoryInterface $formFactory,
        UrlGeneratorInterface $router,
        EventDispatcherInterface $eventDispatcher,
        EntityManagerInterface $entityManager
    ): ResponseInterface {
        $comment = new Comment();
        $comment->setAuthor($tokenStorage->getToken()->getUser());
        $post = $entityManager->getRepository(Post::class)->findOneBy(['slug' => $postSlug]);
        $post->addComment($comment);

        $form = $formFactory->create(CommentType::class, $comment);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($comment);
            $entityManager->flush();

            // When an event is dispatched, Symfony notifies it to all the listeners
            // and subscribers registered to it. Listeners can modify the information
            // passed in the event and they can even modify the execution flow, so
            // there's no guarantee that the rest of this controller will be executed.
            // See https://symfony.com/doc/current/components/event_dispatcher.html
            $eventDispatcher->dispatch(new CommentCreatedEvent($comment));

            return new RedirectResponse((string) $router->generateUri('blog_post', ['slug' => $post->getSlug(), '_locale' => $_locale]));
        }

        return new HtmlResponse(
            $this->template->render('blog/comment_form_error.html.twig', [
                'post' => $post,
                'form' => $form->createView(),
            ])
        );
    }
}
