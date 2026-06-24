<?php
namespace App\Entity;

use App\Enum\DamageState;
use App\Enum\UnitType;
use App\Repository\UnitRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: UnitRepository::class)]
class Unit {
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'units')]
    #[ORM\JoinColumn(nullable: false)]
    private MercenaryCompany $company;

    #[ORM\OneToOne(inversedBy: 'unit')]
    #[ORM\JoinColumn(nullable: true)]
    private ?Pilot $pilot = null;

    #[ORM\Column(length: 255)]
    private string $name;

    #[ORM\Column(length: 255)]
    private string $chassis;

    #[ORM\Column]
    private int $tonnage;

    #[ORM\Column]
    private int $bv;

    #[ORM\Column(length: 50)]
    private UnitType $unitType;

    #[ORM\Column(length: 50)]
    private DamageState $damageState = DamageState::None;

    #[ORM\Column]
    private bool $isActive = true;

    public function getId(): ?int { return $this->id; }

    public function getCompany(): MercenaryCompany { return $this->company; }
    public function setCompany(MercenaryCompany $company): static { $this->company = $company; return $this; }

    public function getPilot(): ?Pilot { return $this->pilot; }
    public function setPilot(?Pilot $pilot): static { $this->pilot = $pilot; return $this; }

    public function getName(): string { return $this->name; }
    public function setName(string $name): static { $this->name = $name; return $this; }

    public function getChassis(): string { return $this->chassis; }
    public function setChassis(string $chassis): static { $this->chassis = $chassis; return $this; }

    public function getTonnage(): int { return $this->tonnage; }
    public function setTonnage(int $tonnage): static { $this->tonnage = $tonnage; return $this; }

    public function getBv(): int { return $this->bv; }
    public function setBv(int $bv): static { $this->bv = $bv; return $this; }

    public function getUnitType(): UnitType { return $this->unitType; }
    public function setUnitType(UnitType $unitType): static { $this->unitType = $unitType; return $this; }

    public function getDamageState(): DamageState { return $this->damageState; }
    public function setDamageState(DamageState $damageState): static { $this->damageState = $damageState; return $this; }

    public function isActive(): bool { return $this->isActive; }
    public function setIsActive(bool $isActive): static { $this->isActive = $isActive; return $this; }
}
