<?php
namespace App\DataTables;
use App\Enum\ContractType;

class ContractTrackTable {
    private const TABLE = [
        ContractType::Raid->value => [
            1 => 'Pushback',
            2 => 'Breakthrough',
            3 => 'Recon',
            4 => 'Strike',
            5 => 'Objective Raid',
            6 => 'Objective Raid',
        ],
        ContractType::Expedition->value => [
            1 => 'Recon',
            2 => 'Recon',
            3 => 'Pursuit',
            4 => 'Flank',
            5 => 'Strike',
            6 => 'Retreat',
        ],
        ContractType::Garrison->value => [
            1 => 'Pursuit',
            2 => 'Meeting Engagement',
            3 => 'Recon',
            4 => 'Pushback',
            5 => 'Strike',
            6 => 'Pursuit',
        ],
        ContractType::Invasion->value => [
            1 => 'Assault',
            2 => 'Breakthrough',
            3 => 'Flank',
            4 => 'Meeting Engagement',
            5 => 'Meeting Engagement',
            6 => 'Pushback',
        ],
        ContractType::Retainer->value => [
            1 => 'Meeting Engagement',
            2 => 'Strike',
            3 => 'Objective Raid',
            4 => 'Objective Raid',
            5 => 'Strike',
            6 => 'Meeting Engagement',
        ],
    ];

    public static function lookup(ContractType $type, int $roll): string {
        return self::TABLE[$type->value][$roll]
            ?? throw new \InvalidArgumentException("Invalid type/roll: {$type->value}/$roll");
    }

    public static function getAllMissionTypes(): array {
        $all = [];
        foreach (self::TABLE as $missions) {
            foreach ($missions as $mission) {
                $all[$mission] = true;
            }
        }
        return array_keys($all);
    }
}
