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

namespace App\Tests;

use Flange\Application;
use PHPUnit\Framework\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected ?Application $app = null;

    protected function tearDown(): void
    {
        parent::tearDown();
        // $this->app = null; // Uncomment this line if you want to reset the application after each test
    }

    protected function makeApp(bool $debug = true): Application
    {
        if (null == $this->app) {
            [$extensions, $config] = require __DIR__.'/../resources/bootstrap.php';
            $this->app = new Application(debug: $debug);
            $this->app->loadExtensions($extensions, $config);
            // $this->app->load(__DIR__ . '/../resources/services.php');
        }

        return $this->app; // Boot Application ...
    }
}
