<?php

namespace App\Entity;

use App\Enum\Payed;
use App\Repository\AdsRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AdsRepository::class)]
class Ads
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $title = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $description = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 0)]
    private ?string $price = null;

    #[ORM\Column(length: 100)]
    private ?string $city = null;

    #[ORM\Column(length: 10)]
    private ?string $poste_code = null;

    #[ORM\Column(enumType: Payed::class)]
    private ?Payed $status = null;

    #[ORM\Column(length: 10)]
    private ?string $reating = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $created_at = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $updated_at = null;

    #[ORM\ManyToOne(inversedBy: 'ads')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\Column(length: 255)]
    private ?string $category = null;

    #[ORM\Column(length: 255)]
    private ?string $region = null;

    #[ORM\Column(type: 'integer', options: ['default' => 0])]
    private int $views = 0;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $photos = [];

    /**
     * @var Collection<int, AdsLike>
     */
    #[ORM\OneToMany(targetEntity: AdsLike::class, mappedBy: 'ads', orphanRemoval: true)]
    private Collection $likes;



    public function __construct()
    {
        $this->likes = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getPrice(): ?string
    {
        return $this->price;
    }

    public function setPrice(string $price): static
    {
        $this->price = $price;

        return $this;
    }

    public function getCity(): ?string
    {
        return $this->city;
    }

    public function setCity(string $city): static
    {
        $this->city = $city;

        return $this;
    }

    public function getPosteCode(): ?string
    {
        return $this->poste_code;
    }

    public function setPosteCode(string $poste_code): static
    {
        $this->poste_code = $poste_code;

        return $this;
    }

    public function getStatus(): ?Payed
    {
        return $this->status?->value;
    }

    public function setStatus(Payed $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getReating(): ?string
    {
        return $this->reating;
    }

    public function setReating(string $reating): static
    {
        $this->reating = $reating;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->created_at;
    }

    public function setCreatedAt(\DateTimeImmutable $created_at): static
    {
        $this->created_at = $created_at;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updated_at;
    }

    public function setUpdatedAt(\DateTimeImmutable $updated_at): static
    {
        $this->updated_at = $updated_at;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;

        return $this;
    }

    public function getCategory(): ?string
    {
        return $this->category;
    }

    public function setCategory(string $category): static
    {
        $this->category = $category;

        return $this;
    }

    public function getRegion(): ?string
    {
        return $this->region;
    }

    public function setRegion(string $region): static
    {
        $this->region = $region;

        return $this;
    }

    public function getPhotos(): array
    {
        return $this->photos ?? [];
    }
    public function setPhotos(?array $photos): self
    {
        $this->photos = $photos ?: [];
        return $this;
    }

    public function addPhoto(string $name): self
    {
        $arr = $this->getPhotos();
        if (!in_array($name, $arr, true)) {
            $arr[] = $name;
            $this->photos = $arr;
        }
        return $this;
    }
    public function removePhoto(string $name): self
    {
        $this->photos = array_values(array_filter($this->getPhotos(), fn($n) => $n !== $name));
        return $this;
    }

    /**
     * @return Collection<int, AdsLike>
     */
    public function getLikes(): Collection
    {
        return $this->likes;
    }

    public function addLike(AdsLike $like): static
    {
        if (!$this->likes->contains($like)) {
            $this->likes->add($like);
            $like->setAds($this);
        }

        return $this;
    }

    public function removeLike(AdsLike $like): static
    {
        if ($this->likes->removeElement($like)) {
            // set the owning side to null (unless already changed)
            if ($like->getAds() === $this) {
                $like->setAds(null);
            }
        }

        return $this;
    }

    public function getViews(): int
    {
        return $this->views;
    }

    public function setViews(int $v): self
    {
        $this->views = $v;
        return $this;
    }
}
