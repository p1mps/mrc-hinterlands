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
        private readonly SalvageCalculationService $salvageCalc,
        private readonly ?ContractResolver $contractResolver = null,
        private readonly ?SalvageRightsParser $salvageRightsParser = null,
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

    /**
     * Create a SalvagedMech and optionally attach it to the active contract.
     *
     * For non-scrapyard mechs, if an active contract exists, the mech is attached
     * to that contract and its salvageRightsPercent is set based on the contract's
     * salvage rights terms.
     *
     * @param SalvagedMech $mechan The mech to create
     * @param MercenaryCompany $company The company that owns the mech
     * @param bool $attachToContract Whether to attempt attachment to active contract (default: true for non-scrapyard)
     * @param ?string $techBase Optional tech base override (defaults to IS)
     * @param ?string $damageState Optional damage state override (defaults to Crippled)
     *
     * @return ?array Resolved contract info if attached, null otherwise
     *   ['contract' => Contract, 'salvageRightsPercent' => int|null, 'adjustedCost' => int]
     */
    public function createMech(
        SalvagedMech $mechan,
        MercenaryCompany $company
    ): ?array {
        $mechan->setCompany($company);
        $this->em->persist($mechan);

        // For non-scrapyard mechs, attempt to attach to active contract
        if (!$mechan->isScrapyard() && $this->contractResolver !== null && $this->salvageRightsParser !== null) {
            $contract = $this->contractResolver->resolveActiveContract($company);

            if ($contract !== null) {
                $salvageRightsPercent = $this->salvageRightsParser->parse($contract->getSalvageRights());

                // Only attach if salvage rights allow it (not Exchange/None)
                $mechan->setContract($contract);
                $mechan->setSalvageRightsPercent($salvageRightsPercent);
                // Add to contract's collection for bidirectional sync
                $contract->getSalvagedMechs()->add($mechan);

                // Calculate adjusted cost: max(0, baseSalvage - salvageRightsValue)
                $baseSalvage = $this->salvageCalc->calculateSalvageValue($mechan->getBvCost());
                $adjustedCost = $this->salvageCalc->calculateAcquisitionCost($baseSalvage, $salvageRightsPercent);

                $this->em->persist($contract);
                $this->em->persist($mechan);

                return [
                    'contract' => $contract,
                    'salvageRightsPercent' => $salvageRightsPercent,
                    'adjustedCost' => $adjustedCost,
                ];
            }
        }

        $this->em->flush();
        return null;
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
