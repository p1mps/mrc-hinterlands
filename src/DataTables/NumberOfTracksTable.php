<?php
namespace App\DataTables;
use App\Enum\ContractType;

class NumberOfTracksTable {
    public static function lookup(ContractType $type, int $roll): int {
        return match($type) {
            ContractType::Raid, ContractType::Expedition => match(true) {
                $roll <= 9  => 2,
                $roll <= 11 => 3,
                default     => 4,
            },
            ContractType::Garrison, ContractType::Retainer => match(true) {
                $roll <= 4  => 1,
                $roll <= 6  => 2,
                $roll <= 8  => 3,
                $roll <= 11 => 4,
                default     => 5,
            },
            ContractType::Invasion => match(true) {
                $roll <= 3  => 2,
                $roll <= 6  => 3,
                $roll <= 10 => 4,
                default     => 5,
            },
        };
    }
}
