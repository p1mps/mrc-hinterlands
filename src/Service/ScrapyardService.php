<?php

namespace App\Service;

use App\Entity\SalvagedMech;
use App\Enum\DamageState;
use App\Enum\TechBase;

class ScrapyardService
{
    /**
     * Scrapyard purchase options tables, keyed by weight class.
     * Each entry: [model => [bvCost, tonnage]]
     * BV values sourced from Master Unit List (masterunitlist.info).
     */
    private const TABLES = [
        'light' => [
            'Locust LCT-3M'  => [522, 20],
            'Wasp WSP-3S'    => [595, 20],
            'Tarantula ZPH-4A'  => [967, 25],
            'Hitman HM-1'    => [925, 30],
            'Osiris OSR-3D'  => [1138, 30],
            'Spider SDR-7K'  => [752, 30],
            'Valkyrie VLK-QD1'  => [984, 50],
            'Garm GRM-01A'   => [701, 35],
            'Panther PNT-10K2'  => [913, 35],
            'Wolfhound WLF-2'  => [1061, 35],
            'Venom SDR-9K'   => [798, 35],
        ],
        'medium' => [
            'Vindicator VND-3L'  => [1105, 45],
            'Assassin ASN-30'    => [925, 40],
            'Hunchback HBK-5N'  => [1041, 50],
            'Bushwacker BSW-X1'  => [1223, 55],
            'Blackjack BJ-2'    => [1148, 45],
            'Dervish DV-9D'     => [1518, 55],
            'Phoenix Hawk PXH-3K'  => [1359, 45],
            'Shadow Hawk SHD-5D'  => [1684, 55],
            'Centurion CN-9Da'  => [1236, 50],
            'Stealth STH-1D'    => [1231, 45],
            'Huron Warrior HUR-W0-R4L'  => [1530, 50],
        ],
        'heavy' => [
            'Catapult CPLT-C5'  => [1748, 65],
            'JagerMech JM6-DDa'  => [911, 65],
            'Archer ARC-5R'     => [1674, 70],
            'Gallowglas GAL-1GLS'  => [1695, 70],
            'Rifleman RFL-5D'   => [1395, 60],
            'Grand Dragon DRG-5K'  => [1358, 60],
            'Marauder MAD-5D'   => [1787, 75],
            'Falconer FLC-8R'   => [2231, 75],
            'War Dog WR-DG-02FC'  => [1814, 75],
            'Rakshasa MDG-1A'   => [1795, 75],
            'Maelstrom MTR-5K'  => [1694, 75],
        ],
        'assault' => [
            'Charger CGR-3Kr'   => [2092, 80],
            'Goliath GOL-3M2'   => [1631, 80],
            'Awesome AWS-9M'    => [1812, 80],
            'Victor VTR-9K/D'   => [1717, 80],
            'BattleMaster BLR-3M'  => [1679, 85],
            'Atlas AS7-K'       => [2175, 100],
            'Stalker STK-5M'    => [1655, 85],
            'Gunslinger GUN-1ERD'  => [2286, 85],
            'Longbow LGB-7V'    => [1816, 85],
            'Cyclops CP-11-B'   => [2145, 90],
            'Cerberus MR-V2'    => [2001, 95],
        ],
    ];

    /**
     * Map 2D6 roll to weight class name.
     * 2–4: Light, 5–7: Medium, 8–10: Heavy, 11–12: Assault.
     */
    private const WEIGHT_ROLLS = [
        2 => 'light',
        3 => 'light',
        4 => 'light',
        5 => 'medium',
        6 => 'medium',
        7 => 'medium',
        8 => 'heavy',
        9 => 'heavy',
        10 => 'heavy',
        11 => 'assault',
        12 => 'assault',
    ];

    /**
     * Map 2D6 roll to DamageState.
     * 2–4: Structural, 5–7: Crippled, 8–10: None, 11–12: ArmorOnly.
     */
    private const CONDITION_ROLLS = [
        2 => 'structural',
        3 => 'structural',
        4 => 'structural',
        5 => 'crippled',
        6 => 'crippled',
        7 => 'crippled',
        8 => 'none',
        9 => 'none',
        10 => 'none',
        11 => 'armor_only',
        12 => 'armor_only',
    ];

    private readonly DiceRoller $diceRoller;
    private readonly SalvageCalculationService $salvageCalc;

    public function __construct(DiceRoller $diceRoller, SalvageCalculationService $salvageCalc)
    {
        $this->diceRoller = $diceRoller;
        $this->salvageCalc = $salvageCalc;
    }

    /**
     * Roll for a scrapyard mech and return a configured SalvagedMech entity.
     */
    public function rollScrapyardMech(): SalvagedMech
    {
        $mechan = new SalvagedMech();
        $mechan->setScrapyard(true);

        // Roll weight class (2D6)
        $weightRoll = $this->diceRoller->roll(2, 6);
        $weightClass = self::WEIGHT_ROLLS[$weightRoll] ?? 'light';

        // Roll model (2D6)
        $modelRoll = $this->diceRoller->roll(2, 6);
        $models = array_keys(self::TABLES[$weightClass]);
        // Map 2–12 to array indices 0–10
        $modelIndex = $modelRoll - 2;
        $modelIndex = max(0, min($modelIndex, count($models) - 1));
        $model = $models[$modelIndex];

        [$bvCost, $tonnage] = self::TABLES[$weightClass][$model];
        $mechan->setModel($model);
        $mechan->setBvCost($bvCost);
        $mechan->setTonnage($tonnage);

        // Roll condition (2D6)
        $conditionRoll = $this->diceRoller->roll(2, 6);
        $damageKey = self::CONDITION_ROLLS[$conditionRoll] ?? 'crippled';
        $mechan->setDamageState(DamageState::from($damageKey));

        // Calculate and set repair cost (defaults to IS tech base)
        $repairCost = $this->salvageCalc->calculateRepairCost($tonnage, $mechan->getDamageState(), TechBase::IS);
        $mechan->setRepairCost($repairCost);

        return $mechan;
    }

    /**
     * Get the table for a given weight class (for display/debugging).
     *
     * @return array<string, array{bvCost: int, tonnage: int}>
     */
    public function getTable(string $weightClass): array
    {
        return self::TABLES[$weightClass] ?? [];
    }

    /**
     * Get all available weight classes.
     *
     * @return string[]
     */
    public function getWeightClasses(): array
    {
        return array_keys(self::TABLES);
    }
}
