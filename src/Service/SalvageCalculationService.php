<?php

namespace App\Service;

use App\Enum\DamageState;
use App\Enum\TechBase;

class SalvageCalculationService
{
    /**
     * Calculate salvage value: BV / 2 (selling price).
     */
    public function calculateSalvageValue(?int $bvCost): ?int
    {
        if ($bvCost === null || $bvCost <= 0) return null;
        return (int) floor($bvCost / 2);
    }

    /**
     * Calculate repair cost based on damage state and tech base.
     * From the SP Activity Cost Table:
     *   ArmorOnly:    Tonnage x 0.5 (IS) / Tonnage x 0.75 (Mixed/Clan)
     *   Structural:   Tonnage x 2 (IS) / Tonnage x 3.5 (Mixed/Clan)
     *   Crippled:     Tonnage x 3 (IS) / Tonnage x 4.5 (Mixed/Clan)
     *   Destroyed:    Tonnage x 5 (IS) / Tonnage x 7.5 (Mixed/Clan)
     */
    public function calculateRepairCost(?int $tonnage, ?DamageState $damageState, ?TechBase $techBase): ?int
    {
        if ($tonnage === null || $tonnage <= 0) return null;
        if ($damageState === null || $damageState === DamageState::None) return 0;
        if ($techBase === null) $techBase = TechBase::IS;

        $multiplier = match($damageState) {
            DamageState::ArmorOnly => $this->getRepairMultiplier($techBase, 0.5),
            DamageState::Structural => $this->getRepairMultiplier($techBase, 2),
            DamageState::Crippled => $this->getRepairMultiplier($techBase, 3),
            DamageState::Destroyed => $this->getRepairMultiplier($techBase, 5),
            default => 0,
        };

        return (int) round($tonnage * $multiplier);
    }

    /**
     * Calculate acquisition cost: salvageValue * (1 - salvageRightsPercent/100).
     * If no salvage rights percent is set (null), defaults to 0% (full salvage value).
     */
    public function calculateAcquisitionCost(?int $salvageValue, ?int $salvageRightsPercent): ?int
    {
        if ($salvageValue === null) return null;
        $percent = $salvageRightsPercent ?? 0;
        return (int) floor($salvageValue * (1 - $percent / 100));
    }

    /**
     * Calculate SP payout: salvageValue * (salvageRightsPercent / 100).
     * If no salvage rights percent is set (null), returns 0 (Exchange with no % = 25% SP).
     */
    public function calculateSpPayout(?int $salvageValue, ?int $salvageRightsPercent): ?int
    {
        if ($salvageValue === null) return null;
        
        // "Exchange" (no %) grants 25% SP payout but prohibits acquisition
        if ($salvageRightsPercent === null) {
            return (int) floor($salvageValue * 0.25);
        }

        return (int) floor($salvageValue * ($salvageRightsPercent / 100));
    }

    /**
     * Check if acquisition is allowed based on salvage rights.
     * "Exchange" (no %) prohibits acquisition.
     * "Exchange/XX%" prohibits acquisition.
     * Regular salvage rights (e.g., "3", "4") allow acquisition.
     */
    public function isAcquisitionAllowed(?int $salvageRightsPercent): bool
    {
        // null means "Exchange" which prohibits acquisition
        if ($salvageRightsPercent === null) return false;
        // 0 means "None" which also prohibits acquisition
        if ($salvageRightsPercent === 0) return false;
        return true;
    }

    private function getRepairMultiplier(TechBase $techBase, float $isMultiplier): float
    {
        return match($techBase) {
            TechBase::IS => $isMultiplier,
            TechBase::Mixed, TechBase::Clan => $isMultiplier * 1.5,
        };
    }
}
