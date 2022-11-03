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

use Doctrine\ORM\Tools\Console\ConsoleRunner;
use Doctrine\ORM\Tools\Console\EntityManagerProvider\SingleManagerProvider;
use Doctrine\Persistence\ObjectManager;
use Flange\KernelInterface;
use Rade\DI\Container;
use Doctrine\ORM\{EntityManager, EntityManagerInterface, ORMSetup};
use Rade\DI\Extensions\{AliasedInterface, BootExtensionInterface, ExtensionInterface};
use Doctrine\ORM\Mapping\{DefaultQuoteStrategy, UnderscoreNamingStrategy};
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Tracy\Bridges\Psr\TracyToPsrLoggerAdapter;

use function Rade\DI\Loader\{param, reference, service, wrap};

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
    public function register(Container $container, array $configs = []): void
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
                ->typed(EntityManager::class, EntityManagerInterface::class, ObjectManager::class),
        ];

        if (!$container instanceof KernelInterface) {
            throw new \RuntimeException('AppExtension can only be registered on a Flange\KernelInterface instance.');
        }

        $container->multiple($definitions);
        $container->load('service.php');
    }

    /**
     * {@inheritdoc}
     */
    public function boot(Container $container): void
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
