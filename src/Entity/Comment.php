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

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

use function Symfony\Component\String\u;

/**
 * Defines the properties of the Comment entity to represent the blog comments.
 * See https://www.doctrine-project.org/projects/doctrine-orm/en/latest/tutorials/getting-started.html.
 *
 * Tip: if you have an existing database, you can generate these entity class automatically.
 * See https://symfony.com/doc/current/doctrine/reverse_engineering.html
 *
 * @author Divine Niiquaye Ibok <divineibok@gmail.com>
 */
#[ORM\Entity, ORM\Table('rade_demo_comment')]
class Comment
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column(type: Types::INTEGER)] private ?int $id = null;
    #[ORM\ManyToOne(Post::class, inversedBy: 'comments'), ORM\JoinColumn(nullable: false)] private ?Post $post = null;
    #[ORM\Column(type: Types::TEXT)] private ?string $content = null;
    #[ORM\Column('published_at', Types::DATETIME_MUTABLE)] private \DateTime $publishedAt;
    #[ORM\ManyToOne(User::class), ORM\JoinColumn(nullable: false)] private ?User $author = null;

    public function __construct()
    {
        $this->publishedAt = new \DateTime();
    }

    public function isLegitComment(): bool
    {
        $containsInvalidCharacters = null !== u($this->content)->indexOf('@');

        return !$containsInvalidCharacters;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function setContent(string $content): void
    {
        $this->content = $content;
    }

    public function getPublishedAt(): \DateTime
    {
        return $this->publishedAt;
    }

    public function setPublishedAt(\DateTime $publishedAt): void
    {
        $this->publishedAt = $publishedAt;
    }

    public function getAuthor(): ?User
    {
        return $this->author;
    }

    public function setAuthor(User $author): void
    {
        $this->author = $author;
    }

    public function getPost(): ?Post
    {
        return $this->post;
    }

    public function setPost(Post $post): void
    {
        $this->post = $post;
    }
}
