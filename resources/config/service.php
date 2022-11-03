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

namespace Rade\DI\Loader;

use Biurad\UI\Renders\TwigRender;
use Psr\Container\ContainerInterface;
use Symfony\Bridge\Twig\{AppVariable, Extension as TwigExtension};
use Twig\{Environment, Extra as TwigExtra};

return static function (\Rade\DI\DefinitionBuilder $builder): void {
    $builder
        ->set('twig.environment', service(Environment::class, [wrap(\Twig\Loader\ArrayLoader::class)]))->public(false)

        ->set('twig.app_variable', service(AppVariable::class))
            ->bind('setDebug', param('debug'))
            ->bind('setRequestStack', reference('request_stack'))
            ->bind('setTokenStorage', reference('security.token_storage'))
            ->public(false)

        ->set('twig.render', service(TwigRender::class, [reference('twig.environment')]))
            ->bind('addRuntimeLoader', wrap(\App\Twig\RuntimeLoader::class))
            ->bind('addExtension', wrap(\App\Twig\AppExtension::class))
            ->bind('addExtension', wrap(\App\Twig\SourceCodeExtension::class))
            ->bind('addExtension', wrap(\App\Twig\WebPackEncoreExtension::class))
            ->bind('addExtension', wrap(TwigExtension\CodeExtension::class, [value('vscode://file/%f:%l'), param('project_dir'), 'UTF-8']))
            ->bind('addExtension', wrap(TwigExtension\CsrfExtension::class))
            ->bind('addExtension', wrap(TwigExtra\Markdown\MarkdownExtension::class))
            ->bind('addExtension', wrap(TwigExtra\Intl\IntlExtension::class))
            ->bind('addExtension', wrap(TwigExtension\AssetExtension::class))
            ->bind('addExtension', wrap(TwigExtension\TranslationExtension::class))
            ->bind('addExtension', wrap(TwigExtension\SecurityExtension::class))
            ->bind('addExtension', wrap(TwigExtension\FormExtension::class))

        ->set('security.post_voter', service(\App\Security\PostVoter::class))
            ->tag('security.voter', ['priority' => 245])
            ->public(false)

        // Load namespaced service definitions into container
        ->namespaced('App\\Repository\\', '../../src/Repository/*')->typed()
        ->namespaced('App\\Form\\', '../../src/Form')->tag('form.type')->public(false)
        ->namespaced('App\\EventListener\\', '../../src/EventListener/*')->tag('event_subscriber')->public(false)

        ->if(fn (ContainerInterface $c) => $c->has('console'))
            ->autowire('security.validator', service(\App\Security\Validator::class))
            ->namespaced('App\\DataFixtures\\', '../../src/DataFixtures/*')->tag('doctrine.data_fixtures_loader')->public(true)
            ->namespaced('App\\Command\\', '../../src/Command/*')->tag('console.command')->public(false)
        ->endIf() // Incase condition is falsy, it reverts to true

    ->load(); // Ensures namespaced service definitions are loaded
};
