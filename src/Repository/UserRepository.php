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

use App\Entity\User;
use Symfony\Component\Security\Core\User\{
    PasswordAuthenticatedUserInterface,
    PasswordUpgraderInterface,
    UserInterface,
    UserProviderInterface
};
use Symfony\Component\Security\Core\Exception\UserNotFoundException;

/**
 * This custom Doctrine repository is empty because so far we don't need any custom
 * method to query for application user information. But it's always a good practice
 * to define a custom repository that will be used when the application grows.
 *
 * See https://symfony.com/doc/current/doctrine.html#querying-for-objects-the-repository
 *
 * @author Divine Niiquaye Ibok <divineibok@gmail.com>
 */
class UserRepository extends AbstractRepository implements UserProviderInterface, PasswordUpgraderInterface
{
    protected const ENTITY_CLASS = User::class;

    /**
     * {@inheritdoc}
     */
    public function supportsClass(string $class): bool
    {
        return self::ENTITY_CLASS === $class || \is_subclass_of($class, self::ENTITY_CLASS);
    }

    /**
     * {@inheritdoc}
     */
    public function loadUserByIdentifier(string $identifier): UserInterface
    {
        if (\filter_var($identifier, \FILTER_VALIDATE_EMAIL)) {
            $user = $this->findOneBy(['email' => $identifier]);
        } elseif (\is_numeric($identifier)) {
            $user = $this->findOneBy(['phone_number' => $identifier]);
        } else {
            $user = $this->findOneBy(['username' => $identifier]);
        }

        if (null === $user) {
            $e = new UserNotFoundException(\sprintf('User "%s" not found.', $identifier));
            $e->setUserIdentifier($identifier);

            throw $e;
        }

        return $user;
    }

    /**
     * {@inheritdoc}
     *
     * @final
     */
    public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void
    {
        $user->setPassword($newHashedPassword); // set the new hashed password on the User object

        // execute the queries on the database
        $this->getEntityManager()->flush();
    }
    /**
     * {@inheritdoc}
     */
    public function refreshUser(UserInterface $user): UserInterface
    {
        // The user must be reloaded via the primary key as all other data
        // might have changed without proper persistence in the database.
        // That's the case when the user has been changed by a form with
        // validation errors.
        if (!$id = $this->getClassMetadata()->getIdentifierValues($user)) {
            throw new \InvalidArgumentException('You cannot refresh a user from the EntityUserProvider that does not contain an identifier. The user object has to be serialized with its own identifier mapped by Doctrine.');
        }

        if (null === $refreshedUser = $this->find($id)) {
            $e = new UserNotFoundException('User with id ' . \json_encode($id) . ' not found.');
            $e->setUserIdentifier(\json_encode($id));

            throw $e;
        }

        return $refreshedUser;
    }
}
