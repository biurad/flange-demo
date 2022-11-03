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

use Flange\Extensions;
use Symfony\Bridge\Twig\AppVariable;

return [
    [
        Extensions\EventDispatcherExtension::class,
        [Extensions\CoreExtension::class, [__DIR__ . '/../']],
        App\AppExtension::class,
        Extensions\Symfony\CacheExtension::class,
        Extensions\AnnotationExtension::class,
        Extensions\TemplateExtension::class,
        Extensions\Symfony\AssetExtension::class,
        Extensions\Symfony\TranslationExtension::class,
        Extensions\Symfony\PropertyAccessExtension::class,
        Extensions\Symfony\ValidatorExtension::class,
        Extensions\Symfony\FormExtension::class,
        Extensions\Symfony\MailerExtension::class,
        Extensions\Doctrine\DatabaseExtension::class,
        [
            Extensions\Security\SecurityExtension::class,
            [
                [
                    //new Extensions\Security\Provider\CaptchaFactory(),
                    new Extensions\Security\Provider\CsrfFactory(),
                    new Extensions\Security\Provider\InMemoryFactory(),
                    new Extensions\Security\Provider\FormLoginFactory(),
                    //new Extensions\Security\Provider\RememberMeFactory(),
                    //new Extensions\SecurityProvider\RemoteUserFactory(),
                ],
            ],
        ],
    ],
    [
        'parameters' => [
            'enabled_locales' => 'en|fr',
            'project_email' => 'anonymous@example.com',
        ],
        'annotation' => ['%project_dir%/src/Controller'],
        'events_dispatcher' => Symfony\Component\EventDispatcher\EventDispatcher::class,
        'assets' => [
            'base_path' => '/build',
            'json_manifest_path' => '%project_dir%/public/build/manifest.json',
        ],
        'config' => [
            'locale' => 'en',
            'paths' => ['%project_dir%/resources/config'],
            //'cache_path' => '%project.var_dir%/cache',
        ],
        'doctrine_dbal' => [
            'connections' => [
                'default' => [
                    'url' => 'sqlite:///%project.var_dir%/data/database.sqlite',
                    //'server_version' => 13, //IMPORTANT: You MUST configure your server version
                ],
            ],
        ],
        'http_galaxy' => [
            'csrf_protection' => true,
            'cookie' => true,
            'session' => [
                'cookie_lifetime' => 0, // 0 means until the browser is closed
                'save_path' => '%project.var_dir%/sessions',
            ],
            'headers' => [
                'response' => [
                    'Flange' => Flange\Application::VERSION,
                ],
            ],
        ],
        'routing' => [
            'cache' => '%project.cache_dir%/load_CachedRoutes.php',
            'import' => [
                '@' => ['prefix' => '/[{_locale:%enabled_locales%}]'],
                // Add more routes here
            ],
            'pipes' => [
                //Biurad\Http\Middlewares\ContentTypeOptionsMiddleware::class,
                //Biurad\Http\Middlewares\ContentLengthMiddleware::class,
            ],
        ],
        'mailer' => [
            'dsn' => 'smtp://localhost',
        ],
        'security' => [
            'throttling' => true,
            'hide_user_not_found' => false,
            'password_hashers' => [
                // Our user class and the algorithm we'll use to encode passwords
                // 'auto' means to let Symfony choose the best possible password hasher (Argon2 or Bcrypt)
                // https://symfony.com/doc/current/security.html#registering-the-user-hashing-passwords
                App\Entity\User::class => 'auto',
                Symfony\Component\Security\Core\User\InMemoryUser::class => 'plaintext',
            ],
            'providers' => [
                // In this example, users are stored via Doctrine in the database
                'database_users' => App\Repository\UserRepository::class,
                'in_memory' => [
                    'memory' => [
                        'users' => [
                            'divine' => [
                                'password' => 'divine',
                                'roles' => ['ROLE_USER'],
                            ],
                            'admin' => [
                                'password' => 'admin',
                                'roles' => ['ROLE_ADMIN'],
                            ],
                        ],
                    ],
                ],
            ],
            'authenticators' => [
                'csrf' => [],
                'form_login' => 'database_users',
            ],
            // Easy way to control access for large sections of your site
            // Note: Only the *first* access control that matches will be used
            'access_control' => [
                ['path' => '^/*(?:%enabled_locales%)?/admin', 'roles' => 'ROLE_ADMIN'], // If user is admin, allow access
                ['path' => '^/*(?:%enabled_locales%)?/(?|blog/comment|profile)', 'roles' => 'ROLE_USER'],
            ],
            'role_hierarchy' => [
                'ROLE_ADMIN' => ['ROLE_USER'],
            ],
        ],
        'templating' => [
            'cache_dir' => '%project.var_dir%/views',
            'paths' => [
                '%project_dir%/resources/templates',
                \dirname((new ReflectionClass(AppVariable::class))->getFileName()) . '/Resources/views/Form',
            ],
            'globals' => [
                'app' => Rade\DI\Loader\reference('twig.app_variable'),
            ],
            'render' => 'twig.render', // uses Biurad\UI\Renders\TwigRender::class
        ],
        'translator' => [
            // Translations are defined using the ICU Message Format
            // See https://symfony.com/doc/current/translation/message_format.html
            'default_path' => '%project_dir%/resources/translations',
            'cache_dir' => '%project.cache_dir%/translations',
            'fallbacks' => ['%default_locale%'],
            //'logging' => true, Uncomment if the "logger" service is registered
        ],
    ],
];
