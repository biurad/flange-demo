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

namespace App\Tests;

use PHPUnit\Framework\TestCase as BaseTestCase;
use Rade\{Application, KernelInterface};

abstract class TestCase extends BaseTestCase
{
    protected Application|KernelInterface|null $app = null;

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->app = null;
    }

    protected function makeApp(bool $debug = true): Application
    {
        if (null !== $booted = $this->app) {
            return $booted;
        }

        [$extensions, $config] = require __DIR__ . '/../resources/bootstrap.php';
        $rade = new Application(debug: $debug);
        $rade->loadExtensions($extensions, $config);
        $rade->load(__DIR__ . '/../resources/services.php');

        return $this->app = $rade; // Boot Application ...
    }
}
