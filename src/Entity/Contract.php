<?php
namespace App\Entity;

use App\Enum\CombatPayTier;
use App\Enum\CommandRights;
use App\Enum\ContractStatus;
use App\Enum\ContractType;
use App\Repository\ContractRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ContractRepository::class)]
class Contract {
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'contracts')]
    #[ORM\JoinColumn(nullable: true)]
    private ?MercenaryCompany $company = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true)]
    private ?MercenaryCompany $opposingCompany = null;

    #[ORM\OneToOne(targetEntity: self::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?Contract $linkedContract = null;

    #[ORM\Column]
    private bool $isOpposing = false;

    #[ORM\Column(length: 50)]
    private ContractType $type;

    #[ORM\Column(length: 255)]
    private string $employer;

    #[ORM\Column(length: 255)]
    private string $employerAffiliation;

    #[ORM\Column]
    private int $scale;

    #[ORM\Column]
    private int $durationMonths;

    #[ORM\Column(nullable: true)]
    private ?int $basePayPercent = null;

    #[ORM\Column(length: 50)]
    private CommandRights $commandRights;

    #[ORM\Column(length: 255)]
    private string $supportTerms;

    #[ORM\Column(length: 255)]
    private string $salvageRights;

    #[ORM\Column(length: 255)]
    private string $transportTerms;

    #[ORM\Column]
    private int $numberOfTracks;

    #[ORM\Column]
    private int $tracksCompleted = 0;

    #[ORM\Column(length: 50)]
    private ContractStatus $status = ContractStatus::Available;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $planet = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $intensity = null;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $acceptedAt = null;

    #[ORM\OneToMany(mappedBy: 'contract', targetEntity: TrackRecord::class, cascade: ['persist', 'remove'])]
    #[ORM\OrderBy(['trackNumber' => 'ASC'])]
    private Collection $trackRecords;

    #[ORM\OneToMany(mappedBy: 'contract', targetEntity: ContractLogEntry::class, cascade: ['persist', 'remove'])]
    #[ORM\OrderBy(['createdAt' => 'ASC'])]
    private Collection $logEntries;

    public function __construct() {
        $this->createdAt = new \DateTimeImmutable();
        $this->trackRecords = new ArrayCollection();
        $this->logEntries = new ArrayCollection();
    }

    public function getId(): ?int { return $this->id; }

    public function getCompany(): ?MercenaryCompany { return $this->company; }
    public function setCompany(?MercenaryCompany $company): static { $this->company = $company; return $this; }

    public function getOpposingCompany(): ?MercenaryCompany { return $this->opposingCompany; }
    public function setOpposingCompany(?MercenaryCompany $company): static { $this->opposingCompany = $company; return $this; }

    public function getLinkedContract(): ?Contract { return $this->linkedContract; }
    public function setLinkedContract(?Contract $contract): static { $this->linkedContract = $contract; return $this; }

    public function isOpposing(): bool { return $this->isOpposing; }
    public function setIsOpposing(bool $isOpposing): static { $this->isOpposing = $isOpposing; return $this; }

    public function getType(): ContractType { return $this->type; }
    public function setType(ContractType $type): static { $this->type = $type; return $this; }

    public function getEmployer(): string { return $this->employer; }
    public function setEmployer(string $employer): static { $this->employer = $employer; return $this; }

    public function getEmployerAffiliation(): string { return $this->employerAffiliation; }
    public function setEmployerAffiliation(string $affiliation): static { $this->employerAffiliation = $affiliation; return $this; }

    public function getScale(): int { return $this->scale; }
    public function setScale(int $scale): static { $this->scale = $scale; return $this; }

    public function getDurationMonths(): int { return $this->durationMonths; }
    public function setDurationMonths(int $months): static { $this->durationMonths = $months; return $this; }

    public function getBasePayPercent(): ?int { return $this->basePayPercent; }
    public function setBasePayPercent(?int $percent): static { $this->basePayPercent = $percent; return $this; }

    public function getCommandRights(): CommandRights { return $this->commandRights; }
    public function setCommandRights(CommandRights $rights): static { $this->commandRights = $rights; return $this; }

    public function getSupportTerms(): string { return $this->supportTerms; }
    public function setSupportTerms(string $terms): static { $this->supportTerms = $terms; return $this; }

    public function getSalvageRights(): string { return $this->salvageRights; }
    public function setSalvageRights(string $rights): static { $this->salvageRights = $rights; return $this; }

    public function getTransportTerms(): string { return $this->transportTerms; }
    public function setTransportTerms(string $terms): static { $this->transportTerms = $terms; return $this; }

    public function getNumberOfTracks(): int { return $this->numberOfTracks; }
    public function setNumberOfTracks(int $n): static { $this->numberOfTracks = $n; return $this; }

    public function getTracksCompleted(): int { return $this->tracksCompleted; }
    public function setTracksCompleted(int $n): static { $this->tracksCompleted = $n; return $this; }

    public function getStatus(): ContractStatus { return $this->status; }
    public function setStatus(ContractStatus $status): static { $this->status = $status; return $this; }

    public function getPlanet(): ?string { return $this->planet; }
    public function setPlanet(?string $planet): static { $this->planet = $planet; return $this; }

    public function getIntensity(): ?string { return $this->intensity; }
    public function setIntensity(?string $intensity): static { $this->intensity = $intensity; return $this; }

    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }

    public function getAcceptedAt(): ?\DateTimeImmutable { return $this->acceptedAt; }
    public function setAcceptedAt(?\DateTimeImmutable $dt): static { $this->acceptedAt = $dt; return $this; }

    public function getTrackRecords(): Collection { return $this->trackRecords; }
    public function getLogEntries(): Collection { return $this->logEntries; }

    public function calculateMonthlyBasePay(): int {
        if ($this->basePayPercent === null) return 0;
        return (int) round(500 * $this->scale * ($this->basePayPercent / 100));
    }

    public function calculateMonthlyCombatPay(CombatPayTier $tier): int {
        return (int) round(500 * $this->scale * $tier->multiplier());
    }
}
