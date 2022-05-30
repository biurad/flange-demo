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

namespace App\Repository;

use Doctrine\ORM\{EntityManagerInterface, EntityRepository};
use Doctrine\ORM\Mapping\ClassMetadata;

/**
 * This custom Doctrine repository.
 *
 * @author Divine Niiquaye Ibok <divineibok@gmail.com>
 */
abstract class AbstractRepository extends EntityRepository
{
    protected const ENTITY_CLASS = '';

    public function __construct(EntityManagerInterface $em, ClassMetadata $class = null)
    {
        parent::__construct($em, $class ?? $em->getClassMetadata(static::ENTITY_CLASS));
    }
}
