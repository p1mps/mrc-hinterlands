<?php

namespace App\Service;

use App\Entity\MercenaryCompany;
use App\Entity\Pilot;
use App\Entity\Unit;
use App\Enum\DamageState;
use App\Service\SalvageCalculationService;
use Doctrine\ORM\EntityManagerInterface;

class RosterService
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly SalvageCalculationService $salvageCalc
    ) {}

    /** @return \Doctrine\Common\Collections\Collection<Unit> */
    public function getUnits(MercenaryCompany $company): \Doctrine\Common\Collections\Collection
    {
        $units = $company->getUnits()->toArray();
        usort($units, fn(Unit $a, Unit $b) => match(true) {
            $a->getDropship() !== null && $b->getDropship() === null => -1,
            $a->getDropship() === null && $b->getDropship() !== null => 1,
            default => 0,
        });
        return new \Doctrine\Common\Collections\ArrayCollection($units);
    }

    /** @return \Doctrine\Common\Collections\Collection<Pilot> */
    public function getPilots(MercenaryCompany $company): \Doctrine\Common\Collections\Collection
    {
        return $company->getPilots();
    }

    public function createUnit(MercenaryCompany $company, Unit $unit): void
    {
        $unit->setCompany($company);
        $this->em->persist($unit);
        $this->em->flush();
    }

    public function updateUnit(Unit $unit): void
    {
        $this->em->flush();
    }

    /**
     * @return string|null Error message if assignment failed, null on success
     */
    public function assignPilotToUnit(Unit $unit, ?int $pilotId, MercenaryCompany $company): ?string
    {
        if ($pilotId === null || $pilotId === 0) {
            $unit->setPilot(null);
            $this->em->flush();
            return null;
        }

        $pilot = $this->em->getRepository(Pilot::class)->find($pilotId);

        if (!$pilot || $pilot->getCompany() !== $company) {
            return 'Pilot not found or does not belong to this company.';
        }

        $unit->setPilot($pilot);
        $this->em->flush();

        return null;
    }

    public function deleteUnit(Unit $unit): void
    {
        $this->em->remove($unit);
        $this->em->flush();
    }

    /**
     * Repairs a unit from its current damage state to None (fully repaired).
     * Deducts SP from company. Returns null on success, error string on failure.
     */
    public function repairUnit(Unit $unit, MercenaryCompany $company): ?string
    {
        $currentDamage = $unit->getDamageState();
        if ($currentDamage === DamageState::None) {
            return 'Unit is already fully repaired.';
        }

        $repairCost = $this->salvageCalc->calculateRepairCost(
            $unit->getTonnage(),
            $currentDamage,
            null
        );

        if ($repairCost === null) {
            return 'Could not calculate repair cost.';
        }

        if ($repairCost === 0) {
            return 'Unit is already fully repaired.';
        }

        try {
            $company->deductSupportPoints($repairCost, 'Repair of ' . $unit->getName() . ' (' . $unit->getChassis() . ')');
        } catch (\Exception $e) {
            return $e->getMessage();
        }

        try {
            $unit->setDamageState(DamageState::None);
        } catch (\ValueError $e) {
            return 'Could not repair unit.';
        }

        $this->em->flush();
        return null;
    }
}
