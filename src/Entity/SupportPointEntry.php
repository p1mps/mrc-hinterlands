<?php
namespace App\Entity;

use App\Repository\SupportPointEntryRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SupportPointEntryRepository::class)]
class SupportPointEntry {
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'supportPointEntries')]
    #[ORM\JoinColumn(nullable: false)]
    private MercenaryCompany $company;

    #[ORM\Column]
    private int $amount;

    #[ORM\Column(length: 255)]
    private string $description;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    public function __construct() {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }

    public function getCompany(): MercenaryCompany { return $this->company; }
    public function setCompany(MercenaryCompany $company): static { $this->company = $company; return $this; }

    public function getAmount(): int { return $this->amount; }
    public function setAmount(int $amount): static { $this->amount = $amount; return $this; }

    public function getDescription(): string { return $this->description; }
    public function setDescription(string $description): static { $this->description = $description; return $this; }

    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
}
