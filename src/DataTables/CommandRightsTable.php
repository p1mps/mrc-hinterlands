<?php
namespace App\DataTables;

class CommandRightsTable {
    private const BASE_STEPS = [
        2=>3,3=>3,4=>3,5=>3,6=>7,7=>7,8=>8,9=>8,10=>8,11=>8,12=>11,
    ];

    private const MAJOR_HOUSES = ['Lyran Commonwealth','Federated Suns','Capellan Confederation','Draconis Combine','Free Worlds League'];

    public static function getBaseStep(int $roll): int { return self::BASE_STEPS[$roll] ?? 7; }

    public static function getModifier(string $employer, string $affiliation, string $contractType): int {
        $mod = 0;
        if ($employer === 'Civilian Organization') $mod += 4;
        if ($employer === 'Mercenary Subcontract') $mod += 4;
        if ($employer === 'Planetary Government') $mod += 1;
        if ($employer === 'House Government') $mod -= 3;
        if ($contractType === 'invasion') $mod -= 2;
        if ($affiliation === 'Free Worlds League') $mod += 1;
        if ($affiliation === 'Draconis Combine') $mod -= 1;
        if ($affiliation === 'Star League') $mod -= 3;
        if (in_array($affiliation, self::MAJOR_HOUSES)) $mod -= 1;
        if ($affiliation === 'Unknown') $mod += 4;
        if ($affiliation === 'Pirate') $mod += 8;
        return $mod;
    }
}
