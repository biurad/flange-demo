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

return static function (\Rade\DI\DefinitionBuilder $builder): void {
    // Write service definitions here ...
    $c = $builder->getContainer();

    // This should be the last definition
    if (\class_exists(\Tracy\Debugger::class)) {
        $c->set('tracy.bar', service('Tracy\Debugger::getBar'))
           ->bind('addPanel', wrap(\Flange\Debug\Tracy\ContainerPanel::class))
           ->bind('addPanel', wrap(\Flange\Debug\Tracy\RoutesPanel::class))
           ->bind('addPanel', wrap(\Flange\Debug\Tracy\TemplatesPanel::class))
        ;
    }

    $builder->load();
};
