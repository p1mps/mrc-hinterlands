<?php
namespace App\DataTables;
use App\Enum\ContractType;

class ContractTypeTable {
    private const RANGES = [
        ['min'=>2,'max'=>4,'type'=>ContractType::Expedition,'duration'=>6,'opposing'=>[
            ['min'=>2,'max'=>7,'type'=>ContractType::Garrison],
            ['min'=>8,'max'=>10,'type'=>ContractType::Raid],
            ['min'=>11,'max'=>12,'type'=>ContractType::Retainer],
        ]],
        ['min'=>5,'max'=>6,'type'=>ContractType::Garrison,'duration'=>6,'opposing'=>[
            ['min'=>2,'max'=>4,'type'=>ContractType::Expedition],
            ['min'=>5,'max'=>9,'type'=>ContractType::Raid],
            ['min'=>10,'max'=>12,'type'=>ContractType::Invasion],
        ]],
        ['min'=>7,'max'=>8,'type'=>ContractType::Raid,'duration'=>3,'opposing'=>[
            ['min'=>2,'max'=>5,'type'=>ContractType::Expedition],
            ['min'=>6,'max'=>8,'type'=>ContractType::Garrison],
            ['min'=>9,'max'=>9,'type'=>ContractType::Raid],
            ['min'=>10,'max'=>11,'type'=>ContractType::Retainer],
            ['min'=>12,'max'=>12,'type'=>ContractType::Invasion],
        ]],
        ['min'=>9,'max'=>9,'type'=>ContractType::Retainer,'duration'=>6,'opposing'=>[
            ['min'=>2,'max'=>5,'type'=>ContractType::Expedition],
            ['min'=>6,'max'=>7,'type'=>ContractType::Raid],
            ['min'=>8,'max'=>9,'type'=>ContractType::Retainer],
            ['min'=>10,'max'=>12,'type'=>ContractType::Invasion],
        ]],
        ['min'=>10,'max'=>12,'type'=>ContractType::Invasion,'duration'=>6,'opposing'=>[
            ['min'=>2,'max'=>4,'type'=>ContractType::Expedition],
            ['min'=>5,'max'=>8,'type'=>ContractType::Garrison],
            ['min'=>9,'max'=>10,'type'=>ContractType::Raid],
            ['min'=>11,'max'=>12,'type'=>ContractType::Retainer],
        ]],
    ];

    public static function lookup(int $roll): array {
        foreach (self::RANGES as $row) {
            if ($roll >= $row['min'] && $roll <= $row['max']) {
                return ['type' => $row['type'], 'duration' => $row['duration'], 'opposing' => $row['opposing']];
            }
        }
        throw new \InvalidArgumentException("Invalid roll: $roll");
    }

    public static function lookupOpposing(ContractType $primaryType, int $roll): ContractType {
        foreach (self::RANGES as $row) {
            if ($row['type'] === $primaryType) {
                foreach ($row['opposing'] as $opp) {
                    if ($roll >= $opp['min'] && $roll <= $opp['max']) {
                        return $opp['type'];
                    }
                }
            }
        }
        throw new \InvalidArgumentException("No opposing contract found for {$primaryType->value} roll $roll");
    }
}
