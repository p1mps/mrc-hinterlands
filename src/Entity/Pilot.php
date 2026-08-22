<?php
namespace App\Entity;

use App\Repository\PilotRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PilotRepository::class)]
class Pilot {
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'pilots')]
    #[ORM\JoinColumn(nullable: false)]
    private MercenaryCompany $company;

    #[ORM\OneToOne(mappedBy: 'pilot')]
    private ?Unit $unit = null;

    #[ORM\Column(length: 255)]
    private string $name;

    #[ORM\Column]
    private bool $isNamed = false;

    #[ORM\Column]
    private int $gunnery = 4;

    #[ORM\Column]
    private int $piloting = 5;

    #[ORM\Column]
    private int $gunneryXp = 0;

    #[ORM\Column]
    private int $pilotingXp = 0;

    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    private bool $wounded = false;

    #[ORM\Column(options: ['default' => 0])]
    private int $edgeTokens = 0;

    public function getId(): ?int { return $this->id; }

    public function getCompany(): MercenaryCompany { return $this->company; }
    public function setCompany(MercenaryCompany $company): static { $this->company = $company; return $this; }

    public function getUnit(): ?Unit { return $this->unit; }

    public function getName(): string { return $this->name; }
    public function setName(string $name): static { $this->name = $name; return $this; }

    public function isNamed(): bool { return $this->isNamed; }
    public function setIsNamed(bool $isNamed): static { $this->isNamed = $isNamed; return $this; }

    public function getGunnery(): int { return $this->gunnery; }
    public function setGunnery(int $gunnery): static { $this->gunnery = $gunnery; return $this; }

    public function getPiloting(): int { return $this->piloting; }
    public function setPiloting(int $piloting): static { $this->piloting = $piloting; return $this; }

    public function getGunneryXp(): int { return $this->gunneryXp; }
    public function setGunneryXp(int $gunneryXp): static { $this->gunneryXp = $gunneryXp; return $this; }

    public function getPilotingXp(): int { return $this->pilotingXp; }
    public function setPilotingXp(int $pilotingXp): static { $this->pilotingXp = $pilotingXp; return $this; }

    public function isWounded(): bool { return $this->wounded; }
    public function setWounded(bool $wounded): static { $this->wounded = $wounded; return $this; }

    public function getEdgeTokens(): int { return $this->edgeTokens; }
    public function setEdgeTokens(int $edgeTokens): static { $this->edgeTokens = $edgeTokens; return $this; }
}
