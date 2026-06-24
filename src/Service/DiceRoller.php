<?php
namespace App\Service;

class DiceRoller {
    public function roll(int $dice, int $sides): int {
        $total = 0;
        for ($i = 0; $i < $dice; $i++) {
            $total += random_int(1, $sides);
        }
        return $total;
    }
}
