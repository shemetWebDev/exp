<?php

namespace App\Entity;

use App\Repository\ArticlesRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ArticlesRepository::class)]
#[ORM\Table(name: 'articles')]
#[ORM\Index(columns: ['create_at'], name: 'idx_articles_created')]
#[ORM\HasLifecycleCallbacks]
class Articles
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[Assert\NotBlank(message: 'Укажите заголовок')]
    #[Assert\Length(max: 255, maxMessage: 'Максимум 255 символов')]
    #[ORM\Column(type: 'string', length: 255)]
    private ?string $title = null;

    #[Assert\NotBlank(message: 'Добавьте описание')]
    #[Assert\Length(min: 10, minMessage: 'Минимум 10 символов')]
    #[ORM\Column(type: 'text')]
    private ?string $description = null;

    // Оставляем ваше имя поля createAt, добавляем алиасы геттер/сеттер
    #[ORM\Column(name: 'create_at', type: 'datetime_immutable')]
    private ?\DateTimeImmutable $createAt = null;

    /** @var Collection<int, Documents> */
    #[ORM\OneToMany(
        mappedBy: 'article',
        targetEntity: Documents::class,
        cascade: ['persist', 'remove'],
        orphanRemoval: true
    )]
    private Collection $documents;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    public function __construct()
    {
        $this->documents = new ArrayCollection();
        $this->createAt  = new \DateTimeImmutable();
    }

    /* ===== Lifecycle ===== */

    #[ORM\PrePersist]
    public function onPrePersist(): void
    {
        if ($this->createAt === null) {
            $this->createAt = new \DateTimeImmutable();
        }
        $this->updatedAt = $this->createAt;
    }

    #[ORM\PreUpdate]
    public function onPreUpdate(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    /* ===== Getters / Setters ===== */

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }
    public function setTitle(string $title): self
    {
        $this->title = $title;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }
    public function setDescription(string $description): self
    {
        $this->description = $description;
        return $this;
    }

    /** Исходное имя, совместимость назад */
    public function getCreateAt(): ?\DateTimeImmutable
    {
        return $this->createAt;
    }
    public function setCreateAt(\DateTimeImmutable $createAt): self
    {
        $this->createAt = $createAt;
        return $this;
    }

    /** Удобный алиас (часто ожидают createdAt) */
    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createAt;
    }
    public function setCreatedAt(\DateTimeImmutable $createdAt): self
    {
        $this->createAt = $createdAt;
        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    /** @return Collection<int, Documents> */
    public function getDocuments(): Collection
    {
        return $this->documents;
    }

    public function addDocument(Documents $document): self
    {
        if (!$this->documents->contains($document)) {
            $this->documents->add($document);
            $document->setArticle($this);
        }
        return $this;
    }

    public function removeDocument(Documents $document): self
    {
        if ($this->documents->removeElement($document)) {
            if ($document->getArticle() === $this) {
                $document->setArticle(null); // orphanRemoval=true удалит строку
            }
        }
        return $this;
    }

    public function __toString(): string
    {
        return (string)($this->title ?? ('Статья #' . $this->id));
    }
}
