<?php
namespace App\DataTables;
use App\Enum\ContractType;

class ContractTrackTable {
    private const TABLE = [
        ContractType::Raid->value      => [1=>'Crush the Head',2=>'Secure',3=>'Ambush',4=>'Reconnaissance',5=>'Bounding Retreat',6=>'Demolition'],
        ContractType::Expedition->value=> [1=>'Ambush',2=>'Evacuation',3=>'Reconnaissance',4=>'Reconnaissance',5=>'Bounding Retreat',6=>'Calamity'],
        ContractType::Garrison->value  => [1=>'Straight Fight',2=>'Secure',3=>'Secure',4=>'Reconnaissance',5=>'Duel',6=>'Calamity'],
        ContractType::Invasion->value  => [1=>'Straight Fight',2=>'Crush the Head',3=>'Secure',4=>'Reinforce',5=>'Calamity',6=>'Demolition'],
        ContractType::Retainer->value  => [1=>'Straight Fight',2=>'Evacuation',3=>'Reinforce',4=>'Reinforce',5=>'Duel',6=>'Demolition'],
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
