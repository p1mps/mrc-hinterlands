<?php

namespace App\Entity;

use App\Enum\DamageState;
use App\Enum\TechBase;
use App\Repository\SalvagedMechRepository;
use Doctrine\ORM\Mapping as ORM;

use App\Entity\MercenaryCompany;

#[ORM\Entity(repositoryClass: SalvagedMechRepository::class)]
class SalvagedMech
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'salvagedMechs')]
    #[ORM\JoinColumn(onDelete: 'SET NULL')]
    private ?Dropship $dropship = null;

    #[ORM\ManyToOne(inversedBy: 'salvagedMechs')]
    #[ORM\JoinColumn(nullable: false)]
    private MercenaryCompany $company;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $model = null;

    #[ORM\Column]
    private int $tonnage;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $bvCost = null;

    #[ORM\ManyToOne(targetEntity: Contract::class)]
    #[ORM\JoinColumn(onDelete: 'SET NULL')]
    private ?Contract $contract = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $contractId = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?DamageState $damageState = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?TechBase $techBase = null;

    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    private bool $isTrulyDestroyed = false;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $spTaken = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $salvageValue = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $salvageRightsPercent = null;

    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    private bool $scrapyard = false;

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

    public function getCompany(): MercenaryCompany
    {
        return $this->company;
    }

    public function setCompany(MercenaryCompany $company): static
    {
        $this->company = $company;
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

    public function getTonnage(): int
    {
        return $this->tonnage;
    }

    public function setTonnage(int $tonnage): static
    {
        $this->tonnage = $tonnage;
        return $this;
    }

    public function getBvCost(): ?int
    {
        return $this->bvCost;
    }

    public function setBvCost(?int $bvCost): static
    {
        $this->bvCost = $bvCost;
        return $this;
    }

    public function getContract(): ?Contract
    {
        return $this->contract;
    }

    public function setContract(?Contract $contract): static
    {
        $this->contract = $contract;
        return $this;
    }

    public function getContractId(): ?int
    {
        return $this->contractId;
    }

    public function setContractId(?int $contractId): static
    {
        $this->contractId = $contractId;
        return $this;
    }

    public function getDamageState(): ?DamageState
    {
        return $this->damageState;
    }

    public function setDamageState(?DamageState $damageState): static
    {
        $this->damageState = $damageState;
        return $this;
    }

    public function getTechBase(): ?TechBase
    {
        return $this->techBase;
    }

    public function setTechBase(?TechBase $techBase): static
    {
        $this->techBase = $techBase;
        return $this;
    }

    public function isTrulyDestroyed(): bool
    {
        return $this->isTrulyDestroyed;
    }

    public function setIsTrulyDestroyed(bool $isTrulyDestroyed): static
    {
        $this->isTrulyDestroyed = $isTrulyDestroyed;
        return $this;
    }

    public function getSpTaken(): ?int
    {
        return $this->spTaken;
    }

    public function setSpTaken(?int $spTaken): static
    {
        $this->spTaken = $spTaken;
        return $this;
    }

    public function getSalvageValue(): ?int
    {
        return $this->salvageValue;
    }

    public function setSalvageValue(?int $salvageValue): static
    {
        $this->salvageValue = $salvageValue;
        return $this;
    }

    public function getSalvageRightsPercent(): ?int
    {
        return $this->salvageRightsPercent;
    }

    public function setSalvageRightsPercent(?int $salvageRightsPercent): static
    {
        $this->salvageRightsPercent = $salvageRightsPercent;
        return $this;
    }

    public function isScrapyard(): bool
    {
        return $this->scrapyard;
    }

    public function setScrapyard(bool $scrapyard): static
    {
        $this->scrapyard = $scrapyard;
        return $this;
    }

    public function getDropship(): ?Dropship
    {
        return $this->dropship;
    }

    public function setDropship(?Dropship $dropship): static
    {
        $this->dropship = $dropship;
        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;
        return $this;
    }
}
