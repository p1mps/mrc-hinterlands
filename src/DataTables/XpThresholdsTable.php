<?php
namespace App\DataTables;

class XpThresholdsTable {
    // skill level => [gunnery_xp_needed, piloting_xp_needed]
    private const THRESHOLDS = [
        5 => [0,    50],
        4 => [50,   100],
        3 => [300,  200],
        2 => [700,  700],
        1 => [1200, 1200],
        0 => [2200, 2200],
    ];

    public static function checkImprovement(int $gunnery, int $piloting, int $gunneryXp, int $pilotingXp): ?string {
        $messages = [];
        $nextGunnery = $gunnery - 1;
        if ($nextGunnery >= 0 && isset(self::THRESHOLDS[$nextGunnery]) && $gunneryXp >= self::THRESHOLDS[$nextGunnery][0]) {
            $messages[] = "Gunnery can improve to $nextGunnery (" . self::THRESHOLDS[$nextGunnery][0] . " XP)";
        }
        $nextPiloting = $piloting - 1;
        if ($nextPiloting >= 0 && isset(self::THRESHOLDS[$nextPiloting]) && $pilotingXp >= self::THRESHOLDS[$nextPiloting][1]) {
            $messages[] = "Piloting can improve to $nextPiloting (" . self::THRESHOLDS[$nextPiloting][1] . " XP)";
        }
        return $messages ? implode(', ', $messages) : null;
    }
}
