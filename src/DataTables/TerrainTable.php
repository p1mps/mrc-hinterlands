<?php
namespace App\DataTables;

class TerrainTable {
    private const TABLE = [
        2  => ['terrain'=>'Desert',           'setting'=>'Desert'],
        3  => ['terrain'=>'Wetlands',         'setting'=>'Swamp'],
        4  => ['terrain'=>'Light Industrial', 'setting'=>'BaseCold'],
        5  => ['terrain'=>'Hills',            'setting'=>'Hills-pastoral'],
        6  => ['terrain'=>'Wooded',           'setting'=>'Wooded-hills'],
        7  => ['terrain'=>'Grasslands',       'setting'=>'Default'],
        8  => ['terrain'=>'Savannahs',        'setting'=>'Default'],
        9  => ['terrain'=>'Urban',            'setting'=>'Town-concrete'],
        10 => ['terrain'=>'Mountains',        'setting'=>'Mountain-lakes-snow'],
        11 => ['terrain'=>'Urban',            'setting'=>'City-high'],
        12 => ['terrain'=>'Alien',            'setting'=>'MountainHotJungle'],
    ];

    public static function lookup(int $roll): array {
        return self::TABLE[$roll] ?? throw new \InvalidArgumentException("Invalid roll: $roll");
    }

    public static function getAllTerrains(): array {
        $seen = [];
        $terrains = [];
        foreach (self::TABLE as $row) {
            if (!in_array($row['terrain'], $seen, true)) {
                $seen[] = $row['terrain'];
                $terrains[$row['terrain']] = $row['setting'];
            }
        }
        return $terrains;
    }

    public static function getSettingByTerrain(string $terrain): string {
        foreach (self::TABLE as $row) {
            if ($row['terrain'] === $terrain) {
                return $row['setting'];
            }
        }
        return $terrain;
    }
}
