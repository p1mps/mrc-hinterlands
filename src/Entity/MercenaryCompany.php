<?php
namespace App\Entity;

use App\Repository\MercenaryCompanyRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: MercenaryCompanyRepository::class)]
class MercenaryCompany {
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\OneToOne(inversedBy: 'company')]
    #[ORM\JoinColumn(nullable: false)]
    private User $user;

    #[ORM\Column(length: 255)]
    private string $name;

    #[ORM\Column(length: 255)]
    private string $faction;

    #[ORM\Column]
    private int $reputation = 1;

    #[ORM\OneToMany(mappedBy: 'company', targetEntity: Unit::class, cascade: ['persist', 'remove'])]
    private Collection $units;

    #[ORM\OneToMany(mappedBy: 'company', targetEntity: Pilot::class, cascade: ['persist', 'remove'])]
    private Collection $pilots;

    #[ORM\OneToMany(mappedBy: 'company', targetEntity: SupportPointEntry::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[ORM\OrderBy(['createdAt' => 'ASC'])]
    private Collection $supportPointEntries;

    #[ORM\OneToMany(mappedBy: 'company', targetEntity: Contract::class)]
    private Collection $contracts;

    public function __construct() {
        $this->units = new ArrayCollection();
        $this->pilots = new ArrayCollection();
        $this->supportPointEntries = new ArrayCollection();
        $this->contracts = new ArrayCollection();
    }

    public function getId(): ?int { return $this->id; }

    public function getUser(): User { return $this->user; }
    public function setUser(User $user): static { $this->user = $user; return $this; }

    public function getName(): string { return $this->name; }
    public function setName(string $name): static { $this->name = $name; return $this; }

    public function getFaction(): string { return $this->faction; }
    public function setFaction(string $faction): static { $this->faction = $faction; return $this; }

    public function getReputation(): int { return $this->reputation; }
    public function setReputation(int $reputation): static { $this->reputation = $reputation; return $this; }

    public function getUnits(): Collection { return $this->units; }
    public function getPilots(): Collection { return $this->pilots; }
    public function getSupportPointEntries(): Collection { return $this->supportPointEntries; }
    public function getContracts(): Collection { return $this->contracts; }

    public function getSupportPointsBalance(): int {
        $total = 0;
        foreach ($this->supportPointEntries as $entry) {
            $total += $entry->getAmount();
        }
        return $total;
    }

    public function getNamedPilotsCount(): int {
        return $this->pilots->filter(fn(Pilot $p) => $p->isNamed())->count();
    }

    public function getTotalBv(): int {
        $total = 0;
        foreach ($this->units as $unit) {
            if ($unit->isActive()) {
                $total += $unit->getBv();
            }
        }
        return $total;
    }
}
