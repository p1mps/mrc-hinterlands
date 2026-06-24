<?php
namespace App\Entity;

use App\Enum\CombatPayTier;
use App\Enum\TrackStatus;
use App\Repository\TrackRecordRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TrackRecordRepository::class)]
class TrackRecord {
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'trackRecords')]
    #[ORM\JoinColumn(nullable: false)]
    private Contract $contract;

    #[ORM\Column]
    private int $trackNumber;

    #[ORM\Column(length: 255)]
    private string $missionType;

    #[ORM\Column(length: 255)]
    private string $terrain;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $commandComplication = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?CombatPayTier $combatPayTier = null;

    #[ORM\Column(length: 50)]
    private TrackStatus $status = TrackStatus::Pending;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $completedAt = null;

    #[ORM\Column]
    private bool $takingOneForTeam = false;

    public function __construct() {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }

    public function getContract(): Contract { return $this->contract; }
    public function setContract(Contract $contract): static { $this->contract = $contract; return $this; }

    public function getTrackNumber(): int { return $this->trackNumber; }
    public function setTrackNumber(int $n): static { $this->trackNumber = $n; return $this; }

    public function getMissionType(): string { return $this->missionType; }
    public function setMissionType(string $type): static { $this->missionType = $type; return $this; }

    public function getTerrain(): string { return $this->terrain; }
    public function setTerrain(string $terrain): static { $this->terrain = $terrain; return $this; }

    public function getCommandComplication(): ?string { return $this->commandComplication; }
    public function setCommandComplication(?string $text): static { $this->commandComplication = $text; return $this; }

    public function getCombatPayTier(): ?CombatPayTier { return $this->combatPayTier; }
    public function setCombatPayTier(?CombatPayTier $tier): static { $this->combatPayTier = $tier; return $this; }

    public function getStatus(): TrackStatus { return $this->status; }
    public function setStatus(TrackStatus $status): static { $this->status = $status; return $this; }

    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }

    public function getCompletedAt(): ?\DateTimeImmutable { return $this->completedAt; }
    public function setCompletedAt(?\DateTimeImmutable $dt): static { $this->completedAt = $dt; return $this; }

    public function isTakingOneForTeam(): bool { return $this->takingOneForTeam; }
    public function setTakingOneForTeam(bool $v): static { $this->takingOneForTeam = $v; return $this; }
}
