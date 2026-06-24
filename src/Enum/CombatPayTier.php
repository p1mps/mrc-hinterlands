<?php
namespace App\Enum;
enum CombatPayTier: string {
    case None      = 'none';
    case Half      = 'half';
    case Full      = 'full';
    case HalfAgain = 'half_again';

    public function multiplier(): float {
        return match($this) {
            self::None      => 0,
            self::Half      => 0.5,
            self::Full      => 1.0,
            self::HalfAgain => 1.5,
        };
    }
}
