<?php
namespace App\Repository;
use App\Entity\Contract;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Persistence\ManagerRegistry;
class ContractRepository extends ServiceEntityRepository {
    public function __construct(ManagerRegistry $registry) {
        parent::__construct($registry, Contract::class);
    }

    public function findAllOrderedByConnections(): array
    {
        return $this->createQueryBuilder('c')
            ->leftJoin('c.linkedContract', 'lc')
            ->leftJoin('c.opposingContracts', 'oc')
            ->addSelect('lc', 'oc')
            ->orderBy('c.createdAt', 'DESC')
            ->addOrderBy('c.isOpposing', 'ASC')
            ->addOrderBy('CASE WHEN lc.id IS NULL THEN c.id ELSE lc.id END', 'ASC')
            ->addOrderBy('c.id', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find the most recent active contract for a company.
     * Active means status is 'Accepted' or 'Active'.
     * Returns null if no active contract exists.
     */
    public function findActiveContractByCompany(\App\Entity\MercenaryCompany $company): ?\App\Entity\Contract
    {
        $conn = $this->getEntityManager()->getConnection();
        $sql = "SELECT * FROM contract 
                WHERE company_id = ? 
                AND status IN ('accepted', 'active') 
                ORDER BY created_at DESC 
                LIMIT 1";
        $row = $conn->fetchAssociative($sql, [$company->getId()]);
        
        if (!$row) {
            return null;
        }
        
        // Reconstruct entity from raw data using reflection (Contract has no setters for most fields)
        $contract = new \App\Entity\Contract();
        $ref = new \ReflectionProperty(\App\Entity\Contract::class, 'id');
        $ref->setValue($contract, (int) $row['id']);
        $ref = new \ReflectionProperty(\App\Entity\Contract::class, 'company');
        $ref->setValue($contract, $company);
        $ref = new \ReflectionProperty(\App\Entity\Contract::class, 'type');
        $ref->setValue($contract, match($row['type']) {
            'expedition' => \App\Enum\ContractType::Expedition,
            'salvage' => \App\Enum\ContractType::Salvage,
            'raiding' => \App\Enum\ContractType::Raiding,
            default => \App\Enum\ContractType::Expedition,
        });
        $ref = new \ReflectionProperty(\App\Entity\Contract::class, 'employer');
        $ref->setValue($contract, $row['employer']);
        $ref = new \ReflectionProperty(\App\Entity\Contract::class, 'employerAffiliation');
        $ref->setValue($contract, $row['employer_affiliation']);
        $ref = new \ReflectionProperty(\App\Entity\Contract::class, 'description');
        $ref->setValue($contract, $row['description']);
        $ref = new \ReflectionProperty(\App\Entity\Contract::class, 'scale');
        $ref->setValue($contract, (int) $row['scale']);
        $ref = new \ReflectionProperty(\App\Entity\Contract::class, 'durationMonths');
        $ref->setValue($contract, (int) $row['duration_months']);
        $ref = new \ReflectionProperty(\App\Entity\Contract::class, 'basePayPercent');
        $ref->setValue($contract, $row['base_pay_percent'] !== null ? (int) $row['base_pay_percent'] : null);
        $ref = new \ReflectionProperty(\App\Entity\Contract::class, 'commandRights');
        $ref->setValue($contract, match($row['command_rights']) {
            'integrated' => \App\Enum\CommandRights::Integrated,
            'operational' => \App\Enum\CommandRights::Operational,
            'tactical' => \App\Enum\CommandRights::Tactical,
            default => \App\Enum\CommandRights::Integrated,
        });
        $ref = new \ReflectionProperty(\App\Entity\Contract::class, 'supportTerms');
        $ref->setValue($contract, $row['support_terms']);
        $ref = new \ReflectionProperty(\App\Entity\Contract::class, 'salvageRights');
        $ref->setValue($contract, $row['salvage_rights']);
        $ref = new \ReflectionProperty(\App\Entity\Contract::class, 'transportTerms');
        $ref->setValue($contract, $row['transport_terms']);
        $ref = new \ReflectionProperty(\App\Entity\Contract::class, 'numberOfTracks');
        $ref->setValue($contract, (int) $row['number_of_tracks']);
        $ref = new \ReflectionProperty(\App\Entity\Contract::class, 'tracksCompleted');
        $ref->setValue($contract, (int) $row['tracks_completed']);
        $ref = new \ReflectionProperty(\App\Entity\Contract::class, 'status');
        $ref->setValue($contract, match($row['status']) {
            'available' => \App\Enum\ContractStatus::Available,
            'accepted' => \App\Enum\ContractStatus::Accepted,
            'active' => \App\Enum\ContractStatus::Active,
            'completed' => \App\Enum\ContractStatus::Completed,
            'broken' => \App\Enum\ContractStatus::Broken,
            default => \App\Enum\ContractStatus::Available,
        });
        $ref = new \ReflectionProperty(\App\Entity\Contract::class, 'name');
        $ref->setValue($contract, $row['name']);
        $ref = new \ReflectionProperty(\App\Entity\Contract::class, 'planet');
        $ref->setValue($contract, $row['planet']);
        $ref = new \ReflectionProperty(\App\Entity\Contract::class, 'intensity');
        $ref->setValue($contract, $row['intensity']);
        $ref = new \ReflectionProperty(\App\Entity\Contract::class, 'createdAt');
        $ref->setValue($contract, new \DateTimeImmutable($row['created_at']));
        $ref = new \ReflectionProperty(\App\Entity\Contract::class, 'acceptedAt');
        $ref->setValue($contract, $row['accepted_at'] !== null ? new \DateTimeImmutable($row['accepted_at']) : null);
        
        return $contract;
    }

    /**
     * Check if a company has an active contract.
     */
    public function hasActiveContract(\App\Entity\MercenaryCompany $company): bool
    {
        return $this->findActiveContractByCompany($company) !== null;
    }
}
