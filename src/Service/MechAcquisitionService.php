<?php

namespace App\Service;

use App\Entity\Dropship;
use App\Entity\MercenaryCompany;
use App\Entity\SalvagedMech;
use App\Entity\Unit;
use App\Enum\DamageState;
use App\Enum\UnitType;
use Doctrine\ORM\EntityManagerInterface;

class MechAcquisitionService
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly SalvageCalculationService $salvageCalc,
    ) {}

    /**
     * Acquires a salvaged mech by deducting support points and creating a new Unit in the roster.
     *
     * For non-scrapyard mechs that have been attached to a contract, the acquisition
     * cost is adjusted by the contract's salvage rights percentage.
     *
     * Scrapyard and non-scrapyard mechs both use the full bvCost for BV assignment.
     * Includes repairCost in the total acquisition cost.
     * The SalvagedMech entry is always removed from the DB after acquisition (both scrapyard and non-scrapyard).
     *
     * @throws \InvalidArgumentException When BV cost is invalid
     * @throws \LogicException When mech has already been acquired
     * @throws \Exception When insufficient support points
     */
    public function acquireMech(SalvagedMech $salvagedMech, MercenaryCompany $company): void
    {
        // Guard: prevent re-acquisition (idempotency check)
        if ($salvagedMech->getContractId() !== null) {
            throw new \LogicException('This mech has already been acquired.');
        }

        // Calculate base salvage value: floor(bvCost / 2)
        $bvCost = $salvagedMech->getBvCost();
        if ($bvCost === null) {
            throw new \InvalidArgumentException('Salvaged Mech must have a valid BV cost or salvage value.');
        }
        $baseSalvage = (int) floor($bvCost / 2);

        // Apply salvage rights discount from contract if present
        // For non-scrapyard mechs attached to a contract, use the contract's salvage rights
        // For scrapyard mechs, use the mech's own salvageRightsPercent (if set manually)
        $salvageRightsPercent = $salvagedMech->getSalvageRightsPercent();

        // Calculate adjusted cost: max(0, baseSalvage * (1 - salvageRightsPercent/100))
        if ($salvageRightsPercent !== null && $salvageRightsPercent > 0) {
            $baseSalvage = (int) floor($baseSalvage * (1 - $salvageRightsPercent / 100));
        }

        // Add repair cost to total acquisition cost
        $repairCost = $salvagedMech->getRepairCost() ?? 0;
        $cost = $baseSalvage + $repairCost;

        if ($cost <= 0) {
            throw new \InvalidArgumentException('Salvaged Mech must have a valid BV cost or salvage value.');
        }

        // Build deduction label with cost breakdown
        $deductionLabel = 'Acquisition of ' . ($salvagedMech->getModel() ?: 'Unknown Mech');

        if ($salvagedMech->isScrapyard()) {
            $deductionLabel .= ' (Scrapyard)';
        }

        if ($salvageRightsPercent !== null && $salvageRightsPercent > 0) {
            $deductionLabel .= ' (salvage rights ' . $salvageRightsPercent . '%)';
        }

        if ($repairCost > 0) {
            $deductionLabel .= ' (includes ' . $repairCost . ' SP repair)';
        }

        // Deduct Support Points from Company (primary failure mechanism)
        $company->deductSupportPoints($cost, $deductionLabel, $this->em->getConnection());

        // Create New Roster Unit
        $newUnit = new Unit();

        // Map fields from SalvagedMech to Unit
        $newUnit->setName($salvagedMech->getModel() ?? 'Unknown Mech');
        $newUnit->setChassis($salvagedMech->getModel() ?? 'Unknown Chassis');
        $newUnit->setTonnage($salvagedMech->getTonnage() ?? 0);
        $newUnit->setTechBase($salvagedMech->getTechBase());

        // BV assignment: both scrapyard and non-scrapyard use the full bvCost
        $newUnit->setBv($salvagedMech->getBvCost());
        $newUnit->setDamageState(DamageState::None);

        try {
            $newUnit->setUnitType(UnitType::Mech);
        } catch (\ValueError $e) {
            throw new \InvalidArgumentException('Could not determine UnitType for Mech. Ensure UnitType::Mech exists.');
        }

        // Link to company
        $newUnit->setCompany($company);

        // Scrapyard: force Crippled status, normal: None (default)
        if ($salvagedMech->isScrapyard()) {
            try {
                $newUnit->setDamageState(DamageState::Crippled);
            } catch (\ValueError $e) {
                throw new \InvalidArgumentException('Could not set Crippled damage state.');
            }
        } else {
            $damageState = $salvagedMech->getDamageState();
            $newUnit->setDamageState($damageState ?? DamageState::None);
        }

        // Unassign from dropship if assigned (frees up capacity)
        if ($salvagedMech->getDropship() !== null) {
            $newUnit->setDropship($salvagedMech->getDropship());
        }

        // Persist Changes
        $this->em->persist($newUnit);
        $this->em->remove($salvagedMech);
        $this->em->flush();
    }
}
