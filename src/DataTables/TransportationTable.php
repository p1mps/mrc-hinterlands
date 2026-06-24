<?php
namespace App\DataTables;

class TransportationTable {
    private const BASE_STEPS = [
        2=>5,3=>5,4=>5,5=>5,6=>6,7=>6,8=>7,9=>7,10=>8,11=>8,12=>9,
    ];

    private const MAJOR_HOUSES = ['Lyran Commonwealth','Federated Suns','Capellan Confederation','Draconis Combine','Free Worlds League'];

    public static function getBaseStep(int $roll): int { return self::BASE_STEPS[$roll] ?? 7; }

    public static function getModifier(string $employer, string $affiliation, string $contractType): int {
        $mod = 0;
        if ($employer === 'House Government') $mod += 1;
        if ($employer === 'Civilian Organization') $mod -= 1;
        if ($contractType === 'garrison') $mod += 1;
        if ($contractType === 'invasion') $mod -= 1;
        if (in_array($affiliation, self::MAJOR_HOUSES)) $mod += 1;
        if ($affiliation === 'Raven Alliance') $mod += 1;
        return $mod;
    }
}
