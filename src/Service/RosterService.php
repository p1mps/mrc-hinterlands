<?php

namespace App\Service;

use App\Entity\MercenaryCompany;
use App\Entity\Pilot;
use App\Entity\Unit;
use App\Enum\ContractStatus;
use App\Enum\DamageState;
use App\Repository\ContractRepository;
use Doctrine\ORM\EntityManagerInterface;

class RosterService
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly SalvageCalculationService $salvageCalc,
        private readonly ContractRepository $contractRepo
    ) {}

    /**
     * Get the support discount multiplier for the given company.
     * Returns 1.0 (no discount) if no active contract or no Straight support.
     */
    private function getSupportDiscount(MercenaryCompany $company): float
    {
        $activeContract = $this->contractRepo->findActiveContractByCompany($company);

        if (!$activeContract) {
            return 1.0;
        }

        $supportPercent = $activeContract->parseSupportPercent();

        return 1.0 - ($supportPercent / 100.0);
    }

    /**
     * Get the support percentage from the active contract.
     * Returns 0 if no active contract or no numeric percentage.
     */
    private function getSupportPercent(MercenaryCompany $company): int
    {
        $activeContract = $this->contractRepo->findActiveContractByCompany($company);
        if (!$activeContract) {
            return 0;
        }
        return $activeContract->getSupportPercent();
    }

    /**
     * Calculate the discounted repair cost for a unit, applying the active
     * contract's Straight support terms if applicable.
     *
     * @return array{baseCost: ?int, cost: ?int, supportPercent: int} Array with base cost, discounted cost, and support percentage
     */
    public function getDiscountedRepairCost(Unit $unit, MercenaryCompany $company): array
    {
        $baseCost = $this->salvageCalc->calculateRepairCost(
            $unit->getTonnage(),
            $unit->getDamageState(),
            $unit->getTechBase()
        );

        if ($baseCost === null) {
            return ['baseCost' => null, 'cost' => null, 'supportPercent' => 0];
        }

        $discount = $this->getSupportDiscount($company);
        $supportPercent = $this->getSupportPercent($company);

        // When discount < 1.0, apply it: floor(baseCost * discount)
        if ($discount < 1.0) {
            return ['baseCost' => $baseCost, 'cost' => (int) floor($baseCost * $discount), 'supportPercent' => $supportPercent];
        }

        return ['baseCost' => $baseCost, 'cost' => $baseCost, 'supportPercent' => $supportPercent];
    }

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
     * Deducts SP from company. If the active contract has Straight support,
     * the repair cost is reduced by the support percentage.
     * Returns null on success, error string on failure.
     */
    public function repairUnit(Unit $unit, MercenaryCompany $company): ?string
    {
        $currentDamage = $unit->getDamageState();
        if ($currentDamage === DamageState::None) {
            return 'Unit is already fully repaired.';
        }

        $repairCost = $this->getDiscountedRepairCost($unit, $company)['cost'];

        if ($repairCost === null) {
            return 'Could not calculate repair cost.';
        }

        if ($repairCost === 0) {
            $unit->setDamageState(DamageState::None);
            $this->em->flush();
            return null;
        }

        try {
            $company->deductSupportPoints($repairCost, 'Repair of ' . $unit->getName() . ' (' . $unit->getChassis() . ')', $this->em->getConnection());
        } catch (\Exception $e) {
            return $e->getMessage();
        }

        try {
            $unit->setDamageState(DamageState::None);
        } catch (ValueError $e) {
            return 'Could not repair unit.';
        }

        $this->em->flush();
        return null;
    }

    /**
     * Processes a battlefield loss: credits support points based on the active
     * contract's Battle support terms, then removes the unit from the roster.
     *
     * If the active contract has "Battle/X%" support, the company receives
     * floor(bv * X / 100) support points. Returns null on success, error string on failure.
     */
    public function battlefieldLoseUnit(Unit $unit, MercenaryCompany $company): ?string
    {
        // 1. Verify ownership
        if ($unit->getCompany() !== $company) {
            return 'You do not own this unit.';
        }

        // 2. Find the active contract
        $activeContract = $this->contractRepo->findActiveContractByCompany($company);

        if (!$activeContract) {
            return 'No active contract found. Battlefield loss requires an active support contract.';
        }

        // 3. Check for "Battle" support type
        if ($activeContract->getSupportType() !== 'Battle') {
            return 'Your active contract does not include Battle support. Battlefield loss is not available.';
        }

        // 4. Extract the percentage from support terms
        $supportPercent = $activeContract->parseSupportPercent();
        if ($supportPercent <= 0) {
            return 'Battle support percentage could not be determined from your contract.';
        }

        // 5. Calculate payout: bv * (X / 100), floored
        $payout = intdiv($unit->getBv() * $supportPercent, 100);
        if ($payout <= 0) {
            return 'Unit BV is too low for the current Battle support percentage.';
        }

        // 6. Credit support points
        $company->addSupportPoints($payout, 'Battlefield loss credit (' . $unit->getName() . ' — ' . $activeContract->getSupportTerms() . ')');

        // 7. Delete the unit
        $this->em->remove($unit);
        $this->em->flush();

        return null;
    }

    /**
     * Rearsms a unit by deducting 10 SP for 1 ton of ammo.
     * If the active contract has Straight support, the rearm cost is reduced by the support percentage.
     * Returns [cost, errorMessage] — cost is null on error, errorMessage is null on success.
     */
    public function rearmUnit(Unit $unit, MercenaryCompany $company): array
    {
        if ($unit->getCompany() !== $company) {
            return [null, 'You do not own this unit.'];
        }

        $baseRearmCost = 10;
        $discount = $this->getSupportDiscount($company);
        $rearmCost = (int) floor($baseRearmCost * $discount);

        try {
            $company->deductSupportPoints($rearmCost, 'Rearm of ' . $unit->getName() . ' (' . $unit->getChassis() . ')', $this->em->getConnection());
        } catch (\Exception $e) {
            return [null, $e->getMessage()];
        }

        $this->em->flush();
        return [$rearmCost, null];
    }
}
