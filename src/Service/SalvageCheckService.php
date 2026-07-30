<?php

namespace App\Service;

use App\Enum\DamageState;

class SalvageCheckService
{
    public function __construct(private readonly DiceRoller $dice) {}

    /**
     * Performs a salvage check for a mech.
     * Rolls 2d6 and returns true if roll >= 4.
     */
    public function rollSalvageCheck(): int
    {
        return $this->dice->roll(2, 6);
    }

    /**
     * Determines if a mech is truly destroyed based on its damage state.
     * A mech is truly destroyed only if its center torso (center torso crit/destroyed) is eliminated.
     * In terms of damage states, "destroyed" means truly destroyed for mechs.
     */
    public function isTrulyDestroyed(?DamageState $damageState): bool
    {
        return $damageState === DamageState::Destroyed;
    }

    /**
     * Returns the salvage check threshold for different unit types.
     * Mech: 4+, Vehicle: 6+, Battle Armor: 7+
     */
    public function getSalvageCheckThreshold(string $unitType): int
    {
        return match(strtolower($unitType)) {
            'vehicle' => 6,
            'battle_armor' => 7,
            default => 4, // mech
        };
    }
}
