<?php

namespace App\Entity;

use App\Repository\AdsLikeRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AdsLikeRepository::class)]
#[ORM\Table(name: 'ads_like')]
#[ORM\UniqueConstraint(name: 'uniq_ads_user', columns: ['ads_id', 'user_id'])]
class AdsLike
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    // FK → ads.id (обратная сторона в Ads: OneToMany(mappedBy="ads") на свойство "likes")
    #[ORM\ManyToOne(targetEntity: \App\Entity\Ads::class, inversedBy: 'likes')]
    #[ORM\JoinColumn(name: 'ads_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private ?\App\Entity\Ads $ads = null;

    // FK → user.id
    #[ORM\ManyToOne(targetEntity: \App\Entity\User::class)]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private ?\App\Entity\User $user = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAds(): ?\App\Entity\Ads
    {
        return $this->ads;
    }

    public function setAds(\App\Entity\Ads $ads): self
    {
        $this->ads = $ads;
        return $this;
    }

    public function getUser(): ?\App\Entity\User
    {
        return $this->user;
    }

    public function setUser(\App\Entity\User $user): self
    {
        $this->user = $user;
        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): self
    {
        $this->createdAt = $createdAt;
        return $this;
    }
}
