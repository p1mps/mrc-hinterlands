<?php

namespace App\Service;

class RansomService
{
    public function __construct(private readonly SalvageCalculationService $salvageCalc) {}

    /**
     * Calculate ransom cost for a mech: salvage value (BV/2).
     * If the player was on an Act of Piracy contract as the pirate, ransom is not allowed.
     */
    public function calculateMechRansomCost(?int $salvageValue): ?int
    {
        return $salvageValue;
    }

    /**
     * Calculate ransom cost for a pilot: (10 - Total Skill) x 100.
     * Total skill = gunnery + piloting.
     * Minimum cost is 0 (cannot ransom for negative SP).
     */
    public function calculatePilotRansomCost(int $gunnery, int $piloting): int
    {
        $totalSkill = $gunnery + $piloting;
        $points = 10 - $totalSkill;
        
        // Cannot ransom for negative SP
        if ($points <= 0) return 0;
        
        return $points * 100;
    }

    /**
     * Check if ransom is allowed for a contract.
     * Not allowed if the player was on an Act of Piracy contract as the pirate.
     */
    public function isRansomAllowed(string $contractType): bool
    {
        // Act of Piracy contracts as the pirate do not allow ransom
        return $contractType !== 'act_of_piracy';
    }
}
