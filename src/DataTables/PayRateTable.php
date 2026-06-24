<?php
namespace App\DataTables;

class PayRateTable {
    private const BASE_STEPS = [
        2=>3,3=>4,4=>4,5=>5,6=>5,7=>6,8=>6,9=>7,10=>7,11=>7,12=>8,
    ];

    private const MAJOR_HOUSES = ['Lyran Commonwealth','Federated Suns','Capellan Confederation','Draconis Combine','Free Worlds League'];

    public static function getBaseStep(int $roll): int { return self::BASE_STEPS[$roll] ?? 6; }

    public static function getModifier(string $employer, string $affiliation, string $contractType): int {
        $mod = 0;
        if ($employer === 'Corporation') $mod += 2;
        if ($employer === 'House Government') $mod += 1;
        if ($employer === 'Civilian Organization') $mod -= 2;
        if ($employer === 'Mercenary Subcontract') $mod -= 1;
        if (in_array($affiliation, self::MAJOR_HOUSES)) $mod += 1;
        if ($affiliation === 'Alyina Mercantile League') $mod += 1;
        if ($affiliation === 'Unknown') $mod += 1;
        if ($affiliation === 'Pirate') $mod -= 3;
        if ($contractType === 'garrison') $mod += 1;
        if ($contractType === 'invasion') $mod -= 1;
        return $mod;
    }
}
