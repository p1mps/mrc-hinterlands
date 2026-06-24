<?php
namespace App\DataTables;

class EmployerAffiliationTable {
    private const MAJOR_HOUSE = [
        2=>'Capellan Confederation', 3=>'Capellan Confederation',
        4=>'Federated Suns', 5=>'Draconis Combine', 6=>'Draconis Combine',
        7=>'Lyran Commonwealth', 8=>'Lyran Commonwealth', 9=>'Lyran Commonwealth',
        10=>'Lyran Commonwealth', 11=>'Lyran Commonwealth', 12=>'Free Worlds League',
    ];

    private const CLAN_AFFILIATION = [
        2=>'Alyina Mercantile League', 3=>'Alyina Mercantile League',
        4=>'Alyina Mercantile League', 5=>'Alyina Mercantile League',
        6=>'Alyina Mercantile League', 7=>'Alyina Mercantile League',
        8=>'Rasalhague Dominion', 9=>'Star League',
        10=>'Raven Alliance', 11=>'Clan Hells Horses', 12=>'Clan Sea Fox',
    ];

    public static function lookup(int $roll, string $employer, int $secondRoll): string {
        if ($employer === 'Clan') {
            return self::CLAN_AFFILIATION[$secondRoll] ?? 'Alyina Mercantile League';
        }
        if ($roll <= 2) return 'Pirate';
        if ($roll === 3) return 'Unknown';
        if ($roll === 4) return 'Mercenary';
        if ($roll <= 6) return 'Periphery Power';
        if ($roll <= 10) return self::MAJOR_HOUSE[$secondRoll] ?? 'Lyran Commonwealth';
        return self::CLAN_AFFILIATION[$secondRoll] ?? 'Alyina Mercantile League';
    }

    public static function isCompatible(string $employer, string $affiliation): bool {
        if ($employer === 'House Government' && in_array($affiliation, ['Pirate', 'Mercenary'])) return false;
        if ($employer === 'Noble (Local)' && (str_contains($affiliation, 'Clan') || str_contains($affiliation, 'Alyina'))) return false;
        return true;
    }
}
