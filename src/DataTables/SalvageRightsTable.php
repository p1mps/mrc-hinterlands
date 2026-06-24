<?php
namespace App\DataTables;

class SalvageRightsTable {
    private const BASE_STEPS = [
        2=>3,3=>3,4=>3,5=>3,6=>4,7=>4,8=>5,9=>5,10=>6,11=>6,12=>7,
    ];

    private const MAJOR_HOUSES = ['Lyran Commonwealth','Federated Suns','Capellan Confederation','Draconis Combine','Free Worlds League'];

    public static function getBaseStep(int $roll): int { return self::BASE_STEPS[$roll] ?? 4; }

    public static function getModifier(string $employer, string $affiliation, string $contractType): int {
        $mod = 0;
        if ($employer === 'Civilian Organization') $mod += 4;
        if ($employer === 'Corporation') $mod += 2;
        if ($employer === 'Planetary Government') $mod += 1;
        if ($employer === 'House Government') $mod -= 2;
        if ($contractType === 'raid') $mod -= 1;
        if ($contractType === 'garrison') $mod -= 2;
        if ($contractType === 'invasion') $mod += 1;
        if (in_array($affiliation, self::MAJOR_HOUSES)) $mod -= 1;
        if ($affiliation === 'Federated Suns') $mod += 1;
        if ($affiliation === 'Unknown') $mod += 1;
        if ($affiliation === 'Pirate') $mod += 9;
        return $mod;
    }
}
