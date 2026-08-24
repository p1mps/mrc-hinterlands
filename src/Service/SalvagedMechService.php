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

    public function createMech(
        SalvagedMech $mechan,
        MercenaryCompany $company,
        bool $attachToContract = true,
        ?string $techBase = null,
        ?string $damageState = null,
    ): ?array {
        $mechan->setCompany($company);

        // Normalize: strip non-alphanumeric, uppercase → match backed enum values
        $normalize = fn(string $s): string => strtoupper(preg_replace('/[^a-zA-Z0-9]/', '', $s));

        // Apply optional overrides from controller
        if ($techBase !== null) {
            $mechan->setTechBase(match($normalize($techBase)) {
                'IS' => \App\Enum\TechBase::IS,
                'CLAN' => \App\Enum\TechBase::Clan,
                'MIXED' => \App\Enum\TechBase::Mixed,
                default => null,
            });
        }

        if ($damageState !== null) {
            $mechan->setDamageState(match($normalize($damageState)) {
                'ARMORONLY' => \App\Enum\DamageState::ArmorOnly,
                'STRUCTURAL' => \App\Enum\DamageState::Structural,
                'CRIPPLED' => \App\Enum\DamageState::Crippled,
                'DESTROYED' => \App\Enum\DamageState::Destroyed,
                default => null,
            });
        }

        $this->em->persist($mechan);

        // For non-scrapyard mechs, attempt to attach to active contract
        if (!$mechan->isScrapyard() && $attachToContract && $this->contractResolver !== null && $this->salvageRightsParser !== null) {
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

                $this->em->flush();  // Flush BEFORE returning so the mech is saved to DB

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
