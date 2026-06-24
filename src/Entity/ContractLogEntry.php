<?php
namespace App\Entity;

use App\Enum\ContractLogEntryType;
use App\Repository\ContractLogEntryRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ContractLogEntryRepository::class)]
class ContractLogEntry {
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'logEntries')]
    #[ORM\JoinColumn(nullable: false)]
    private Contract $contract;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true)]
    private ?TrackRecord $track = null;

    #[ORM\Column]
    private int $month;

    #[ORM\Column(length: 50)]
    private ContractLogEntryType $entryType;

    #[ORM\Column(type: 'text')]
    private string $description;

    #[ORM\Column(nullable: true)]
    private ?int $rollResult = null;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $data = null;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    public function __construct() {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }

    public function getContract(): Contract { return $this->contract; }
    public function setContract(Contract $contract): static { $this->contract = $contract; return $this; }

    public function getTrack(): ?TrackRecord { return $this->track; }
    public function setTrack(?TrackRecord $track): static { $this->track = $track; return $this; }

    public function getMonth(): int { return $this->month; }
    public function setMonth(int $month): static { $this->month = $month; return $this; }

    public function getEntryType(): ContractLogEntryType { return $this->entryType; }
    public function setEntryType(ContractLogEntryType $type): static { $this->entryType = $type; return $this; }

    public function getDescription(): string { return $this->description; }
    public function setDescription(string $description): static { $this->description = $description; return $this; }

    public function getRollResult(): ?int { return $this->rollResult; }
    public function setRollResult(?int $result): static { $this->rollResult = $result; return $this; }

    public function getData(): ?array { return $this->data; }
    public function setData(?array $data): static { $this->data = $data; return $this; }

    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
}
