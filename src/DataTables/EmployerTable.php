<?php
namespace App\DataTables;

class EmployerTable {
    private const TABLE = [
        2  => 'Civilian Organization',
        3  => 'Mercenary Subcontract',
        4  => 'Planetary Government',
        5  => 'Noble (Local)',
        6  => 'House Government',
        7  => 'Mercenary Subcontract',
        8  => 'House Government',
        9  => 'Corporation',
        10 => 'Noble (Local)',
        11 => 'Mercenary Subcontract',
        12 => 'Clan',
    ];

    public static function lookup(int $roll): string {
        return self::TABLE[$roll] ?? throw new \InvalidArgumentException("Invalid roll: $roll");
    }
}
