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

namespace App;

use Biurad\UI\Renders\TwigRender;
use Doctrine\ORM\Tools\Console\ConsoleRunner;
use Doctrine\ORM\Tools\Console\EntityManagerProvider\SingleManagerProvider;
use Doctrine\Persistence\ObjectManager;
use Rade\Database\Doctrine\Provider\EntityUserProvider;
use Rade\DI\AbstractContainer;
use Doctrine\ORM\{EntityManager, EntityManagerInterface, ORMSetup};
use Rade\DI\Extensions\{AliasedInterface, BootExtensionInterface, ExtensionInterface};
use Doctrine\ORM\Mapping\{DefaultQuoteStrategy, UnderscoreNamingStrategy};
use Symfony\Bridge\Twig\{AppVariable, Extension as TwigExtension};
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\String\Slugger\AsciiSlugger;
use Tracy\Bridges\Psr\TracyToPsrLoggerAdapter;
use Twig\{Environment, Extra as TwigExtra};
use Twig\Loader\ArrayLoader;

use function Rade\DI\Loader\{param, reference, service, tagged, value, wrap};

/**
 * The default app extension.
 *
 * @author Divine Niiquaye Ibok <divineibok@gmail.com>
 */
class AppExtension implements AliasedInterface, ConfigurationInterface, BootExtensionInterface, ExtensionInterface
{
    /**
     * {@inheritdoc}
     */
    public function getAlias(): string
    {
        return 'app';
    }

    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder(__CLASS__);

        $treeBuilder->getRootNode()
            ->addDefaultsIfNotSet()
            ->beforeNormalization()
                ->ifTrue(fn ($k) => \is_array($k) && \array_is_list($k))
                ->then(fn (array $v): array => ['paths' => $v])
            ->end()
            ->children()
                ->arrayNode('paths')
                    ->prototype('scalar')->end()
                    ->defaultValue(['%project_dir%/src/Entity'])
                ->end()
                ->scalarNode('data_fixtures_path')->defaultValue('%project_dir%/app/DataFixtures')->end()
                ->scalarNode('proxy_dir')->defaultValue('%project.cache_dir%/proxies')->end()
            ->end();

        return $treeBuilder;
    }

    /**
     * {@inheritdoc}
     */
    public function register(AbstractContainer $container, array $configs): void
    {
        $definitions = [
            //'logger' => service(TracyToPsrLoggerAdapter::class, [wrap('Tracy\Debugger::getLogger')])->autowire(),
            'doctrine.orm.config' => service(ORMSetup::class . '::createAttributeMetadataConfiguration')
                ->args([$configs['paths'], param('debug'), $configs['proxy_dir'], reference('cache.system')])
                ->bind('setNamingStrategy', wrap(UnderscoreNamingStrategy::class, [0, true]))
                ->bind('setQuoteStrategy', wrap(DefaultQuoteStrategy::class))
                ->public(false),
            'doctrine.orm.entity_manager' => service(EntityManager::class . '::create')
                ->args([reference('doctrine.dbal_connection.default'), reference('doctrine.orm.config')])
                ->autowire([EntityManager::class, EntityManagerInterface::class, ObjectManager::class]),
            'doctrine.orm.entity_provider' => service(EntityUserProvider::class, [1 => Entity\User::class])->public(false),
            'security.post_voter' => service(Security\PostVoter::class)
                ->tag('security.voter', ['priority' => 245])
                ->public(false),
            'twig.environment' => service(Environment::class, [wrap(ArrayLoader::class)]),
            'twig.render' => service(TwigRender::class, [reference('twig.environment')])
                ->bind('addRuntimeLoader', wrap(Twig\RuntimeLoader::class))
                ->bind('addExtension', wrap(Twig\AppExtension::class, [param('enabled_locales')]))
                ->bind('addExtension', wrap(Twig\SourceCodeExtension::class))
                ->bind('addExtension', wrap(Twig\WebPackEncoreExtension::class, ['%project_dir%/public/build/entrypoints.json']))
                ->bind('addExtension', wrap(TwigExtension\CodeExtension::class, [value('vscode://file/%f:%l'), param('project_dir'), 'UTF-8']))
                ->bind('addExtension', wrap(TwigExtension\CsrfExtension::class))
                ->bind('addExtension', wrap(TwigExtra\Markdown\MarkdownExtension::class))
                ->bind('addExtension', wrap(TwigExtra\Intl\IntlExtension::class))
                ->bind('addExtension', wrap(TwigExtension\AssetExtension::class))
                ->bind('addExtension', wrap(TwigExtension\TranslationExtension::class))
                ->bind('addExtension', wrap(TwigExtension\SecurityExtension::class))
                ->bind('addExtension', wrap(TwigExtension\FormExtension::class)),
            'twig.app_variable' => service(AppVariable::class)
                ->bind('setDebug', param('debug'))
                ->bind('setRequestStack', reference('request_stack'))
                ->bind('setTokenStorage', reference('security.token_storage'))
                ->public(false),
            'form.type.post' => service(Form\PostType::class, [wrap(AsciiSlugger::class, [param('default_locale')])])
                ->tag('form.type')
                ->public(false),
            'form.type.tags' => service(Form\Type\TagsInputType::class)
                ->tag('form.type')
                ->public(false),
            'app.events_listener' => service(EventListener\AppEventListener::class)
                ->tag('event_subscriber')
                ->public(false),
        ];

        if ($container->has('console')) {
            $definitions += [
                'security.validator' => service(Security\Validator::class)->autowire(),
                'doctrine.data_fixtures_loader' => service(DataFixtures\AppFixtures::class)
                    ->tag('doctrine.data_fixtures_loader')
                    ->public(false),
                'console.command.orm_data_fixtures' => service(Command\DataFixturesCommand::class)
                    ->arg(1, tagged('doctrine.data_fixtures_loader'))
                    ->tag('console.command')
                    ->public(false),
                'console.command.add_user' => service(Command\AddUserCommand::class)
                    ->tag('console.command')
                    ->public(false),
                'console.command.delete_user' => service(Command\DeleteUserCommand::class)
                    ->tag('console.command')
                    ->public(false),
                'console.command.list_users' => service(Command\ListUsersCommand::class, [1 => param('project_email')])
                    ->tag('console.command')
                    ->public(false),
            ];
        }

        $container->multiple($definitions);
    }

    /**
     * {@inheritdoc}
     */
    public function boot(AbstractContainer $container): void
    {
        if ($container->has('console')) {
            $doctrineEntity = 'doctrine.orm.entity_manager';

            if ($container->has($doctrineEntity)) {
                $args = [1 => wrap(SingleManagerProvider::class, [reference($doctrineEntity)])];
                $container->definition('console')->call(wrap(ConsoleRunner::class . '::addCommands', $args), true);
            }
        }
    }
}
