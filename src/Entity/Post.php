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

namespace App\Entity;

use App\Repository\PostRepository;
use Doctrine\Common\Collections\{ArrayCollection, Collection};
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * Defines the properties of the Post entity to represent the blog posts.
 *
 * See https://www.doctrine-project.org/projects/doctrine-orm/en/latest/tutorials/getting-started.html
 *
 * Tip: if you have an existing database, you can generate these entity class automatically.
 * See https://symfony.com/doc/current/doctrine/reverse_engineering.html
 *
 * @author Divine Niiquaye Ibok <divineibok@gmail.com>
 */
#[ORM\Entity(PostRepository::class), ORM\Table('rade_demo_post')]
class Post
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column(type: Types::INTEGER)] private ?int $id = null;
    #[ORM\Column(type: Types::STRING)] private ?string $title = null;
    #[ORM\Column(type: Types::STRING)] private ?string $slug = null;
    #[ORM\Column(type: Types::STRING)] private ?string $summary = null;
    #[ORM\Column(type: Types::TEXT)] private ?string $content = null;
    #[ORM\Column('published_at', Types::DATETIME_MUTABLE)] private \DateTime $publishedAt;
    #[ORM\ManyToOne(User::class, ['persist']), ORM\JoinColumn(nullable: false)] private ?User $author = null;
    /** @var Comment[]|Collection */
    #[ORM\OneToMany('post', Comment::class, cascade: ['persist'], orphanRemoval: true)] private Collection $comments;
    /** @var Tag[]|Collection */
    #[ORM\ManyToMany(Tag::class, cascade: ['persist']), ORM\JoinTable('rade_demo_post_tag'), ORM\OrderBy(['name' => 'ASC'])]
    private Collection $tags;

    public function __construct()
    {
        $this->publishedAt = new \DateTime();
        $this->comments = new ArrayCollection();
        $this->tags = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(?string $title): void
    {
        $this->title = $title;
    }

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): void
    {
        $this->slug = $slug;
    }

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function setContent(?string $content): void
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

    public function getComments(): Collection
    {
        return $this->comments;
    }

    public function addComment(Comment $comment): void
    {
        $comment->setPost($this);

        if (!$this->comments->contains($comment)) {
            $this->comments->add($comment);
        }
    }

    public function removeComment(Comment $comment): void
    {
        $this->comments->removeElement($comment);
    }

    public function getSummary(): ?string
    {
        return $this->summary;
    }

    public function setSummary(?string $summary): void
    {
        $this->summary = $summary;
    }

    public function addTag(Tag ...$tags): void
    {
        foreach ($tags as $tag) {
            if (!$this->tags->contains($tag)) {
                $this->tags->add($tag);
            }
        }
    }

    public function removeTag(Tag $tag): void
    {
        $this->tags->removeElement($tag);
    }

    public function getTags(): Collection
    {
        return $this->tags;
    }
}
