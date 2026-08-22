<?php
namespace App\Entity;

use App\Entity\Dropship;
use App\Repository\MercenaryCompanyRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Connection;
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

    #[ORM\OneToOne(mappedBy: 'company', cascade: ['persist', 'remove'])]
    private ?Dropship $dropship = null;

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

    public function adjustReputation(int $delta): static {
        $this->reputation = max(0, $this->reputation + $delta);
        return $this;
    }

    public function scaleFromReputation(): int {
        if ($this->reputation <= 2) return 1;
        if ($this->reputation <= 10) return 2;
        return 3;
    }

    public function getMaxNegotiationSteps(): int {
        return 2 * $this->scaleFromReputation();
    }

    public function getUnits(): Collection { return $this->units; }
    public function getPilots(): Collection { return $this->pilots; }
    public function getSupportPointEntries(): Collection { return $this->supportPointEntries; }
    public function getContracts(): Collection { return $this->contracts; }

    public function getDropship(): ?Dropship { return $this->dropship; }

    public function getSupportPointsBalance(): int {
        $total = 0;
        foreach ($this->supportPointEntries as $entry) {
            $total += $entry->getAmount();
        }
        return $total;
    }

    /**
     * Deducts support points by creating a negative entry.
     *
     * @param int $amount The amount to deduct (must be positive)
     * @param string $reason Description of why points were deducted
     * @throws \Exception if insufficient funds
     */
    public function deductSupportPoints(int $amount, string $reason = 'General Deduction', ?Connection $conn = null): void {
        if ($amount <= 0) {
            throw new \InvalidArgumentException('Deduction amount must be positive.');
        }

        // When a Connection is provided, query the database directly for the balance.
        // This handles the case where data was seeded via raw SQL (e.g. in acceptance tests)
        // and the in-memory collection is stale or empty.
        if ($conn !== null && $this->id !== null) {
            $currentBalance = (int) $conn->fetchOne(
                'SELECT COALESCE(SUM(amount), 0) FROM support_point_entry WHERE company_id = ?',
                [$this->id]
            );
        } else {
            $currentBalance = $this->getSupportPointsBalance();
        }

        if ($currentBalance < $amount) {
            throw new \Exception("Insufficient support points. Current balance: {$currentBalance}, Requested deduction: {$amount}");
        }

        $entry = new SupportPointEntry();
        if (method_exists($entry, 'setAmount')) {
            $entry->setAmount(-$amount);
        } else {
            throw new \RuntimeException('SupportPointEntry does not have setAmount method.');
        }

        if (method_exists($entry, 'setCompany')) {
            $entry->setCompany($this);
        } else {
            throw new \RuntimeException('SupportPointEntry does not have setCompany method.');
        }

        if (method_exists($entry, 'setDescription')) {
            $entry->setDescription($reason);
        }

        $this->supportPointEntries->add($entry);
    }

    public function addSupportPoints(int $amount, string $reason = 'General Credit'): void {
        if ($amount <= 0) {
            throw new \InvalidArgumentException('Added amount must be positive.');
        }

        $entry = new SupportPointEntry();
        $entry->setAmount($amount);
        $entry->setCompany($this);
        $entry->setDescription($reason);

        $this->supportPointEntries->add($entry);
    }

    public function getNamedPilotsCount(): int {
        return $this->pilots->filter(fn(Pilot $p) => $p->isNamed())->count();
    }

    public function getTotalBv(): int {
        $total = 0;
        foreach ($this->units as $unit) {
            if ($unit->getDropship() !== null) {
                $total += $unit->getBv();
            }
        }
        return $total;
    }
}
