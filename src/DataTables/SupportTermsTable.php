<?php
namespace App\DataTables;

class SupportTermsTable {
    private const BASE_STEPS = [
        2=>3,3=>4,4=>4,5=>4,6=>4,7=>5,8=>5,9=>6,10=>6,11=>6,12=>7,
    ];

    public static function getBaseStep(int $roll): int { return self::BASE_STEPS[$roll] ?? 5; }

    public static function getModifier(string $employer, string $affiliation, string $contractType): int {
        $mod = 0;
        if ($contractType === 'invasion') $mod += 2;
        if ($contractType === 'expedition') $mod += 1;
        if ($employer === 'House Government') $mod += 2;
        if ($employer === 'Planetary Government') $mod += 1;
        if ($employer === 'Corporation') $mod -= 2;
        if ($employer === 'Civilian Organization') $mod -= 2;
        if (in_array($affiliation, ['Capellan Confederation','Federated Suns','Unknown'])) $mod -= 1;
        if ($affiliation === 'Pirate') $mod -= 3;
        return $mod;
    }
}
