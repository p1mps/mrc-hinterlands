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

    #[ORM\Column(length: 255)]
    private string $name;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $model = null;

    #[ORM\Column(nullable: true)]
    private ?int $tonnage = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $configuration = null;

    #[ORM\ManyToOne(inversedBy: 'salvagedMechs')]
    #[ORM\JoinColumn(nullable: false)]
    private ?ContractLogEntry $sourceLogEntry = null;

    #[ORM\Column]
    private \DateTimeImmutable $salvagedAt;

    public function __construct()
    {
        $this->salvagedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;
        return $this;
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

    public function getConfiguration(): ?string
    {
        return $this->configuration;
    }

    public function setConfiguration(?string $configuration): static
    {
        $this->configuration = $configuration;
        return $this;
    }

    public function getSourceLogEntry(): ?ContractLogEntry
    {
        return $this->sourceLogEntry;
    }

    public function setSourceLogEntry(?ContractLogEntry $sourceLogEntry): static
    {
        $this->sourceLogEntry = $sourceLogEntry;
        return $this;
    }

    public function getSalvagedAt(): \DateTimeImmutable
    {
        return $this->salvagedAt;
    }

    public function setSalvagedAt(\DateTimeImmutable $salvagedAt): static
    {
        $this->salvagedAt = $salvagedAt;
        return $this;
    }
}
