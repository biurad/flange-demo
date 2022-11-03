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

namespace App\Twig;

use Symfony\Bridge\Twig\Extension\CsrfRuntime;
use Symfony\Bridge\Twig\Form\TwigRendererEngine;
use Symfony\Component\Form\FormRenderer;
use Symfony\Contracts\Service\{ServiceProviderInterface, ServiceSubscriberInterface};
use Twig\Extra\Markdown\{LeagueMarkdown, MarkdownRuntime};
use Twig\RuntimeLoader\RuntimeLoaderInterface;

/**
 * Default runtime loader for Twig.
 *
 * @author Divine Niiquaye Ibok <divineibok@gmail.com>
 */
class RuntimeLoader implements RuntimeLoaderInterface, ServiceSubscriberInterface
{
    public function __construct(private ServiceProviderInterface $container)
    {
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedServices(): array
    {
        return [
            'http.csrf.token_manager',
            'twig.environment',
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function load($class)
    {
        if (MarkdownRuntime::class === $class) {
            return new MarkdownRuntime(new LeagueMarkdown());
        }

        if (CsrfRuntime::class === $class) {
            return new CsrfRuntime($this->container->get('http.csrf.token_manager'));
        }

        if (FormRenderer::class === $class) {
            $defaultThemes = [
                'form_div_layout.html.twig',
                'form/layout.html.twig',
                'form/fields.html.twig',
            ];

            return new FormRenderer(new TwigRendererEngine($defaultThemes, $this->container->get('twig.environment')), $this->container->get('http.csrf.token_manager'));
        }

        return null;
    }
}
