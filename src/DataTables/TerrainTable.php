<?php
namespace App\DataTables;

class TerrainTable {
    private const TABLE = [
        'Desert',
        'Grasslands',
        'Savannahs',
        'Urban',
        'A Game of Armored Combat',
        'Clan Invasion',
        'GM Choice :)',
    ];

    public static function lookup(int $roll): string {
        return self::TABLE[$roll] ?? throw new \InvalidArgumentException("Invalid roll: $roll");
    }

    public static function getAllTerrains(): array {
        return self::TABLE;
    }

    public static function getSettingByTerrain(string $terrain): string {
        foreach (self::TABLE as $row) {
            if ($row === $terrain) {
                return $row;
            }
        }
        return $terrain;
    }
}
