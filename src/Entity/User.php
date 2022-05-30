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

namespace App\Entity;

use App\Repository\UserRepository;
use Biurad\Security\Interfaces\CredentialsHolderInterface;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\{EquatableInterface, UserInterface};

/**
 * Defines the properties of the User entity to represent the application users.
 * See https://www.doctrine-project.org/projects/doctrine-orm/en/latest/tutorials/getting-started.html.
 *
 * Tip: if you have an existing database, you can generate these entity class automatically.
 * See https://symfony.com/doc/current/doctrine/reverse_engineering.html
 *
 * @author Divine Niiquaye Ibok <divineibok@gmail.com>
 */
#[ORM\Entity(UserRepository::class), ORM\Table('rade_demo_user')]
class User implements CredentialsHolderInterface, EquatableInterface
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column(type: Types::INTEGER)] private ?int $id = null;
    #[ORM\Column('full_name', Types::STRING)] private ?string $fullName = null;
    #[ORM\Column(type: Types::STRING, unique: true)] private ?string $username = null;
    #[ORM\Column(type: Types::STRING, unique: true)] private ?string $email = null;
    #[ORM\Column(type: Types::STRING)] private ?string $password = null;
    #[ORM\Column(type: Types::JSON)] private array $roles = [];
    #[ORM\Column(type: Types::BOOLEAN)] private bool $enabled = true;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setEnabled(bool $enabled = true): void
    {
        $this->enabled = $enabled;
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function setFullName(string $fullName): void
    {
        $this->fullName = $fullName;
    }

    public function getFullName(): ?string
    {
        return $this->fullName;
    }

    public function getUserIdentifier(): string
    {
        return $this->username;
    }

    public function getUsername(): string
    {
        return $this->getUserIdentifier();
    }

    public function setUsername(string $username): void
    {
        $this->username = $username;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): void
    {
        $this->email = $email;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): void
    {
        $this->password = $password;
    }

    /**
     * Returns the roles or permissions granted to the user for security.
     */
    public function getRoles(): array
    {
        $roles = $this->roles;

        // guarantees that a user always has at least one role for security
        if (empty($roles)) {
            $roles[] = 'ROLE_USER';
        }

        return \array_unique($roles);
    }

    /**
     * @param array<int,string> $roles
     */
    public function setRoles(array $roles): void
    {
        $this->roles = $roles;
    }

    /**
     * Removes sensitive data from the user.
     *
     * {@inheritdoc}
     */
    public function eraseCredentials(): void
    {
        // if you had a plainPassword property, you'd nullify it here
        // $this->plainPassword = null;
    }

    /**
     * {@inheritdoc}
     */
    public function isEqualTo(UserInterface $user): bool
    {
        return $this->id === $user->getId() && $this->getUserIdentifier() === $user->getUserIdentifier();
    }

    public function __serialize(): array
    {
        // add $this->salt too if you don't use Bcrypt or Argon2i
        return [$this->id, $this->username, $this->password];
    }

    public function __unserialize(array $data): void
    {
        // add $this->salt too if you don't use Bcrypt or Argon2i
        [$this->id, $this->username, $this->password] = $data;
    }
}
