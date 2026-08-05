<?php

namespace App\Entity;

use App\Entity\SalvagedMech;
use App\Repository\DropshipRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: DropshipRepository::class)]
class Dropship
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\OneToOne(inversedBy: 'dropship', cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(nullable: false, unique: true)]
    private MercenaryCompany $company;

    #[ORM\OneToMany(mappedBy: 'dropship', targetEntity: SalvagedMech::class)]
    private Collection $salvagedMechs;

    #[ORM\OneToMany(mappedBy: 'dropship', targetEntity: Unit::class)]
    private Collection $unitsOnDropship;

    #[ORM\Column(type: 'integer')]
    private int $maxCapacity;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $name = null;

    #[ORM\Column(type: 'integer')]
    private int $mekbayCapacity = 0;

    public function __construct()
    {
        $this->salvagedMechs = new ArrayCollection();
        $this->unitsOnDropship = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(?int $id): static
    {
        $this->id = $id;
        return $this;
    }

    public function getCompany(): MercenaryCompany
    {
        return $this->company;
    }

    public function setCompany(MercenaryCompany $company): static
    {
        $this->company = $company;
        return $this;
    }

    public function getMaxCapacity(): int
    {
        return $this->maxCapacity;
    }

    public function setMaxCapacity(int $maxCapacity): static
    {
        $this->maxCapacity = $maxCapacity;
        return $this;
    }

    public function getSalvagedMechs(): Collection
    {
        return $this->salvagedMechs;
    }

    public function getUnitsOnDropship(): Collection
    {
        return $this->unitsOnDropship;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): static
    {
        $this->name = $name;
        return $this;
    }

    public function getMekbayCapacity(): int
    {
        return $this->mekbayCapacity;
    }

    public function setMekbayCapacity(int $mekbayCapacity): static
    {
        $this->mekbayCapacity = $mekbayCapacity;
        return $this;
    }
}
