<?php

namespace App\Entity;

use App\Repository\AmenityRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AmenityRepository::class)]
class Amenity
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    /**
     * @var Collection<int, Space>
     */
    #[ORM\ManyToMany(targetEntity: Space::class, inversedBy: 'amenities')]
    private Collection $space;

    public function __construct()
    {
        $this->space = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return Collection<int, Space>
     */
    public function getSpace(): Collection
    {
        return $this->space;
    }

    public function addSpace(Space $space): static
    {
        if (!$this->space->contains($space)) {
            $this->space->add($space);
        }

        return $this;
    }

    public function removeSpace(Space $space): static
    {
        $this->space->removeElement($space);

        return $this;
    }
}
