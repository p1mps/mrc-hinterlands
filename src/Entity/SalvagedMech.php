<?php

namespace App\Entity;

use App\Repository\SalvagedMechRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SalvagedMechRepository::class)]
class SalvagedMech
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $model = null;

    #[ORM\Column(nullable: true)]
    private ?int $tonnage = null;

    // NEW FIELD: BV Cost
    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $bvCost = null;

    // NEW FIELD: Acquired status
    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    private bool $acquired = false;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getModel(): ?string
    {
        return $this->model;
    }

    public function setModel(?string $model): static
    {
        $this->model = $model;
        return $this;
    }

    public function getTonnage(): ?int
    {
        return $this->tonnage;
    }

    public function setTonnage(?int $tonnage): static
    {
        $this->tonnage = $tonnage;
        return $this;
    }

    // NEW GETTER/SETTER FOR BV COST
    public function getBvCost(): ?int
    {
        return $this->bvCost;
    }

    public function setBvCost(?int $bvCost): static
    {
        $this->bvCost = $bvCost;
        return $this;
    }

    // NEW GETTER/SETTER FOR ACQUIRED STATUS
    public function isAcquired(): bool
    {
        return $this->acquired;
    }

    public function setAcquired(bool $acquired): static
    {
        $this->acquired = $acquired;
        return $this;
    }
}
