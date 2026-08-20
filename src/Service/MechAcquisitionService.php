<?php

namespace App\Service;

use App\Entity\Dropship;
use App\Entity\MercenaryCompany;
use App\Entity\SalvagedMech;
use App\Entity\Unit;
use App\Enum\DamageState;
use App\Enum\UnitType;
use App\Service\SalvageCalculationService;
use Doctrine\ORM\EntityManagerInterface;

class MechAcquisitionService
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly SalvageCalculationService $salvageCalc
    ) {}

    /**
     * Acquires a salvaged mech by deducting support points and creating a new Unit in the roster.
     *
     * Uses salvageValue (if set) or falls back to bvCost for backward compatibility.
     * Includes repairCost in the total acquisition cost.
     * The SalvagedMech entry is NOT removed from the DB — it stays as a record.
     *
     * @throws \Exception
     */
    public function acquireMech(SalvagedMech $salvagedMech, MercenaryCompany $company): void
    {
        $cost = $salvagedMech->getAcquisitionCost();

        if ($cost <= 0) {
            throw new \InvalidArgumentException('Salvaged Mech must have a valid BV cost or salvage value.');
        }

        // Deduct Support Points from Company
        $deductionLabel = 'Acquisition of ' . ($salvagedMech->getModel() ?: 'Unknown Mech');
        if ($salvagedMech->isScrapyard()) {
            $deductionLabel .= ' (Scrapyard)';
        }
        $repairCost = $salvagedMech->getRepairCost();
        if ($repairCost > 0) {
            $deductionLabel .= ' (includes ' . $repairCost . ' SP repair)';
        }
        $company->deductSupportPoints($cost, $deductionLabel);

        // Create New Roster Unit
        $newUnit = new Unit();

        // Map fields from SalvagedMech to Unit
        $newUnit->setName($salvagedMech->getModel() ?? 'Unknown Mech');
        $newUnit->setChassis($salvagedMech->getModel() ?? 'Unknown Chassis');
        $newUnit->setTonnage($salvagedMech->getTonnage() ?? 0);

        // BV assignment: scrapyard → half base cost, non-scrapyard with salvageValue → salvageValue, else → base cost
        if ($salvagedMech->isScrapyard()) {
            $newUnit->setBv(floor($salvagedMech->getBvCost() / 2));
        } elseif ($salvagedMech->getSalvageValue() !== null) {
            $newUnit->setBv($salvagedMech->getSalvageValue());
        } else {
            $newUnit->setBv($salvagedMech->getBvCost());
        }
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

        // Guard: prevent re-acquisition
        if ($salvagedMech->getContractId() !== null) {
            throw new \LogicException('This mech has already been acquired.');
        }

        // Unassign from dropship if assigned (frees up capacity)
        if ($salvagedMech->getDropship() !== null) {
            $salvagedMech->setDropship(null);
        }

        // Persist Changes
        // Scrapyard mechs stay in DB (per docstring); non-scrapyard are removed
        $this->em->persist($newUnit);
        if (!$salvagedMech->isScrapyard()) {
            $this->em->remove($salvagedMech);
        }
        $this->em->flush();
    }
}
