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

namespace App\Tests\Console;

use App\Tests\TestCase;
use App\Tests\Traits\InteractsWithConsole;

class CommandTest extends TestCase
{
    use InteractsWithConsole;

    /**
     * @runInSeparateProcess
     */
    public function testCommandActionWorks(): void
    {
        $command = $this->runCommand('security:list-users');
        $command->assertCommandIsSuccessful();

        $command = $this->getCommand('security:list-users');
        $this->assertEquals(['security:users'], $command->getAliases());
    }
}
