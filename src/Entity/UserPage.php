<?php

namespace App\Entity;

use App\Repository\UserPageRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: UserPageRepository::class)]
class UserPage
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $title = null;

    #[ORM\Column(length: 200)]
    private ?string $slug = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $keywords = null;

    #[ORM\Column(length: 255)]
    private ?string $subtitle = null;

    #[ORM\Column(length: 255)]
    private ?string $bannerImg = null;

    #[ORM\Column(length: 255)]
    private ?string $image = null;

    #[ORM\Column(length: 255)]
    private ?string $advantageOne = null;

    #[ORM\Column(length: 255)]
    private ?string $advantageTwoo = null;

    #[ORM\Column(length: 255)]
    private ?string $advantageThree = null;

    #[ORM\Column(length: 255)]
    private ?string $phone = null;

    #[ORM\Column(length: 255)]
    private ?string $adress = null;

    #[ORM\Column(length: 255)]
    private ?string $email = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $companyName = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $mapPosition = null;

    #[ORM\ManyToOne(inversedBy: 'userPages')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

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

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): static
    {
        $this->slug = $slug;

        return $this;
    }

    public function getKeywords(): ?string
    {
        return $this->keywords;
    }

    public function setKeywords(string $keywords): static
    {
        $this->keywords = $keywords;

        return $this;
    }

    public function getSubtitle(): ?string
    {
        return $this->subtitle;
    }

    public function setSubtitle(string $subtitle): static
    {
        $this->subtitle = $subtitle;

        return $this;
    }

    public function getBannerImg(): ?string
    {
        return $this->bannerImg;
    }

    public function setBannerImg(string $bannerImg): static
    {
        $this->bannerImg = $bannerImg;

        return $this;
    }

    public function getImage(): ?string
    {
        return $this->image;
    }

    public function setImage(string $image): static
    {
        $this->image = $image;

        return $this;
    }

    public function getAdvantageOne(): ?string
    {
        return $this->advantageOne;
    }

    public function setAdvantageOne(string $advantageOne): static
    {
        $this->advantageOne = $advantageOne;

        return $this;
    }

    public function getAdvantageTwoo(): ?string
    {
        return $this->advantageTwoo;
    }

    public function setAdvantageTwoo(string $advantageTwoo): static
    {
        $this->advantageTwoo = $advantageTwoo;

        return $this;
    }

    public function getAdvantageThree(): ?string
    {
        return $this->advantageThree;
    }

    public function setAdvantageThree(string $advantageThree): static
    {
        $this->advantageThree = $advantageThree;

        return $this;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function setPhone(string $phone): static
    {
        $this->phone = $phone;

        return $this;
    }

    public function getAdress(): ?string
    {
        return $this->adress;
    }

    public function setAdress(string $adress): static
    {
        $this->adress = $adress;

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    public function getCompanyName(): ?string
    {
        return $this->companyName;
    }

    public function setCompanyName(?string $companyName): static
    {
        $this->companyName = $companyName;

        return $this;
    }

    public function getMapPosition(): ?string
    {
        return $this->mapPosition;
    }

    public function setMapPosition(string $mapPosition): static
    {
        $this->mapPosition = $mapPosition;

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
}
