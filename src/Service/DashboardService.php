<?php

namespace App\Service;

use App\Entity\Contract;
use App\Entity\MercenaryCompany;
use App\Enum\ContractStatus;
use Doctrine\ORM\EntityManagerInterface;

class DashboardService
{
    public function __construct(private readonly EntityManagerInterface $em) {}

    /** @return Contract[] */
    public function getActiveContracts(): array
    {
        return $this->em->getRepository(Contract::class)->findBy(
            ['status' => ContractStatus::Active]
        );
    }

    /** @return MercenaryCompany[] */
    public function getAllCompanies(): array
    {
        return $this->em->getRepository(MercenaryCompany::class)->findAll();
    }

    /** @return array<array{company: MercenaryCompany, supportPoints: int}> Sorted by support points descending */
    public function getCompaniesWithSupportPoints(array $companies): array
    {
        $result = [];
        foreach ($companies as $c) {
            $result[] = [
                'company' => $c,
                'supportPoints' => $c->getSupportPointsBalance(),
            ];
        }

        usort($result, fn($a, $b) => $b['supportPoints'] <=> $a['supportPoints']);
        return $result;
    }
}
