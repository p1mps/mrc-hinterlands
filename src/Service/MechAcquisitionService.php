<?php

namespace App\Service;

use App\Entity\MercenaryCompany;
use App\Entity\SalvagedMech;
use App\Entity\Unit;
use App\Enum\UnitType;
use Doctrine\ORM\EntityManagerInterface;

class MechAcquisitionService
{
    public function __construct(private readonly EntityManagerInterface $em) {}

    /**
     * Acquires a salvaged mech by deducting support points, creating a new Unit in the roster,
     * and removing the SalvagedMech entry.
     *
     * @throws \Exception
     */
    public function acquireMech(SalvagedMech $salvagedMech, MercenaryCompany $company): void
    {
        // 1. Validate BV Cost
        if ($salvagedMech->getBvCost() === null || $salvagedMech->getBvCost() <= 0) {
            throw new \InvalidArgumentException('Salvaged Mech must have a valid BV cost.');
        }

        $bvCost = $salvagedMech->getBvCost();

        // 2. Deduct Support Points from Company
        // This will throw an exception if insufficient funds
        $company->deductSupportPoints($bvCost, "Acquisition of {$salvagedMech->getModel()}");

        // 3. Create New Roster Unit
        $newUnit = new Unit();

        // Map fields from SalvagedMech to Unit
        $newUnit->setName($salvagedMech->getModel() ?? '');
        $newUnit->setChassis($salvagedMech->getModel() ?? 'Unknown Chassis');
        $newUnit->setTonnage($salvagedMech->getTonnage() ?? 0);
        $newUnit->setBv($bvCost); // Using BV cost as the BV value for the unit

        // Set Unit Type to Mech (assuming UnitType enum has a Mech case)
        try {
            $newUnit->setUnitType(UnitType::Mech);
        } catch (\ValueError $e) {
            // Fallback if 'Mech' is not defined in UnitType enum
            // Try to find a suitable type or throw error
            throw new \InvalidArgumentException('Could not determine UnitType for Mech. Ensure UnitType::Mech exists.');
        }

        // Link to company
        $newUnit->setCompany($company);

        // 4. Mark Salvaged Mech as Acquired (optional if we delete immediately, but good for audit)
        $salvagedMech->setAcquired(true);

        // 5. Persist Changes
        $this->em->persist($newUnit);
        $this->em->remove($salvagedMech);
        $this->em->flush();
    }
}
