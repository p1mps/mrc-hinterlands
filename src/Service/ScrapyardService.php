<?php

namespace App\Service;

use App\Entity\SalvagedMech;
use App\Enum\DamageState;

class ScrapyardService
{
    /**
     * Scrapyard purchase options tables, keyed by weight class.
     * Each entry: [model => [bvCost, tonnage]]
     */
    private const TABLES = [
        'light' => [
            'Locust LCT-3M'  => [482, 35],
            'Wasp WSP-3S'    => [389, 25],
            'Tarantula ZPH-4A'  => [597, 30],
            'Hitman HM-1'    => [925, 32],
            'Osiris OSR-3D'  => [1138, 32],
            'Spider SDR-7K'  => [482, 35],
            'Valkyrie VLK-QD1'  => [984, 50],
            'Garm GRM-01A'   => [482, 35],
            'Panther PNT-10K2'  => [913, 22],
            'Wolfhound WLF-2'  => [768, 40],
            'Venom SDR-9K'   => [482, 35],
        ],
        'medium' => [
            'Vindicator VND-3L'  => [1520, 45],
            'Assassin ASN-30'    => [1275, 45],
            'Hunchback HBK-5N'  => [1485, 50],
            'Bushwacker BSW-X1'  => [1390, 45],
            'Blackjack BJ-2'    => [1265, 45],
            'Dervish DV-9D'     => [1425, 45],
            'Phoenix Hawk PXH-3K'  => [1380, 45],
            'Shadow Hawk SHD-5D'  => [1495, 45],
            'Centurion CN-9Da'  => [1310, 45],
            'Stealth STH-1D'    => [1290, 45],
            'Huron Warrior HUR-W0-R4L'  => [1440, 45],
        ],
        'heavy' => [
            'Catapult CPLT-C5'  => [1810, 60],
            'JagerMech JM6-DDa'  => [1279, 45],
            'Archer ARC-5R'     => [1685, 60],
            'Gallowglas GAL-1GLS'  => [1425, 45],
            'Rifleman RFL-5D'   => [1445, 50],
            'Grand Dragon DRG-5K'  => [1520, 55],
            'Marauder MAD-5D'   => [1790, 60],
            'Falconer FLC-8R'   => [2150, 60],
            'War Dog WR-DG-02FC'  => [1485, 45],
            'Rakshasa MDG-1A'   => [1795, 45],
            'Maelstrom MTR-5K'  => [1620, 55],
        ],
        'assault' => [
            'Charger CGR-3Kr'   => [2180, 60],
            'Goliath GOL-3M2'   => [1720, 60],
            'Awesome AWS-9M'    => [2850, 90],
            'Victor VTR-9K/D'   => [1625, 55],
            'BattleMaster BLR-3M'  => [1680, 55],
            'Atlas AS7-K'       => [2650, 75],
            'Stalker STK-5M'    => [1820, 60],
            'Gunslinger GUN-1ERD'  => [2380, 65],
            'Longbow LGB-7V'    => [1890, 60],
            'Cyclops CP-11-B'   => [2580, 75],
            'Cerberus MR-V2'    => [1685, 55],
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

    public function __construct(DiceRoller $diceRoller)
    {
        $this->diceRoller = $diceRoller;
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
