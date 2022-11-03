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

namespace App\Tests\Traits;

trait InteractsWithFaker
{
    protected ?\Faker\Generator $faker;

    /**
     * Create a Faker instance for the given locale.
     */
    protected function makeFaker(string $locale = null): \Faker\Generator
    {
        return $this->faker ??= \Faker\Factory::create($locale ?? \Faker\Factory::DEFAULT_LOCALE);
    }
}
