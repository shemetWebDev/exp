<?php

namespace App\Entity;

use App\Repository\DocumentsRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: DocumentsRepository::class)]
#[ORM\Table(name: 'documents')]
#[ORM\HasLifecycleCallbacks]
class Documents
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    // Имя файла на диске (уникальное)
    #[ORM\Column(type: 'string', length: 255)]
    private string $path;

    // MIME-тип
    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $type = null;

    // Оригинальное имя (для скачивания)
    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $originalName = null;

    // Размер в байтах (опционально)
    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $size = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\ManyToOne(inversedBy: 'documents')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Articles $article = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    /* ===== Getters / Setters ===== */

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPath(): string
    {
        return $this->path;
    }
    public function setPath(string $path): self
    {
        $this->path = $path;
        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }
    public function setType(?string $type): self
    {
        $this->type = $type;
        return $this;
    }

    public function getOriginalName(): ?string
    {
        return $this->originalName;
    }
    public function setOriginalName(?string $originalName): self
    {
        $this->originalName = $originalName;
        return $this;
    }

    public function getSize(): ?int
    {
        return $this->size;
    }
    public function setSize(?int $size): self
    {
        $this->size = $size;
        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getArticle(): ?Articles
    {
        return $this->article;
    }
    public function setArticle(?Articles $article): self
    {
        $this->article = $article;
        return $this;
    }
}
