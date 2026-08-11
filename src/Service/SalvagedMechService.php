<?php

namespace App\Service;

use App\Entity\MercenaryCompany;
use App\Entity\SalvagedMech;
use Doctrine\ORM\EntityManagerInterface;

class SalvagedMechService
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly MechAcquisitionService $acquisition,
        private readonly SalvageCalculationService $salvageCalc
    ) {}

    /** @return SalvagedMech[] */
    public function getAllMechs(MercenaryCompany $company): array
    {
        return $this->em->getRepository(SalvagedMech::class)->findByCompanyOrderedByCreatedAt($company);
    }

    public function getMech(int $id): ?SalvagedMech
    {
        return $this->em->getRepository(SalvagedMech::class)->find($id);
    }

    public function createMech(SalvagedMech $mechan, MercenaryCompany $company): void
    {
        $mechan->setCompany($company);
        $this->em->persist($mechan);
        $this->em->flush();
    }

    public function updateMech(SalvagedMech $mechan): void
    {
        $this->em->flush();
    }

    public function deleteMech(SalvagedMech $mechan): void
    {
        $this->em->remove($mechan);
        $this->em->flush();
    }

    public function acquireMech(SalvagedMech $mechan, MercenaryCompany $company): void
    {
        $this->acquisition->acquireMech($mechan, $company);
    }

    public function calculateSalvageValue(?int $bvCost): ?int
    {
        return $this->salvageCalc->calculateSalvageValue($bvCost);
    }

    public function calculateRepairCost(?int $tonnage, ?string $damageState, ?string $techBase): ?int
    {
        return $this->salvageCalc->calculateRepairCost($tonnage, $damageState, $techBase);
    }

    public function calculateAcquisitionCost(?int $salvageValue, ?int $salvageRightsPercent): ?int
    {
        return $this->salvageCalc->calculateAcquisitionCost($salvageValue, $salvageRightsPercent);
    }

    public function calculateSpPayout(?int $salvageValue, ?int $salvageRightsPercent): ?int
    {
        return $this->salvageCalc->calculateSpPayout($salvageValue, $salvageRightsPercent);
    }
}
