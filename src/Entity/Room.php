<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use App\Enum\BedEnum;
use App\Repository\RoomRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: RoomRepository::class)]
#[ApiResource(normalizationContext: ['groups' => ['room : read']])]
class Room
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['room : read'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['room : read'])]
    private ?string $title = null;

    //#[ORM\Column(enumType: BedEnum::class)]
    #[ORM\Column(type: 'string', enumType: BedEnum::class)]
    #[Groups(['room : read'])]
    private ?BedEnum $bed = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Groups(['room : read'])]
    private ?string $description = null;

    #[ORM\Column]
    #[Groups(['room : read'])]
    private ?int $price = null;

    #[ORM\ManyToOne(inversedBy: 'rooms')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Space $space = null;

    #[ORM\Column(length: 255)]
    #[Groups(['room : read'])]
    private ?string $image = null;

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

    public function getBed(): ?BedEnum
    {
        return $this->bed;
    }

    public function setBed(BedEnum $bed): static
    {
        $this->bed = $bed;

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

    public function getPrice(): ?int
    {
        return $this->price;
    }

    public function setPrice(int $price): static
    {
        $this->price = $price;

        return $this;
    }

    public function getSpace(): ?Space
    {
        return $this->space;
    }

    public function setSpace(?Space $space): static
    {
        $this->space = $space;

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
}
