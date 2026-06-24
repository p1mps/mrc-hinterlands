<?php
namespace App\Enum;
enum CommandRights: string {
    case Integrated = 'integrated';
    case House = 'house';
    case Liaison = 'liaison';
    case Independent = 'independent';

    public function complicationBonus(): int {
        return match($this) {
            self::Integrated => 3,
            self::House      => 2,
            self::Liaison    => 1,
            self::Independent => 0,
        };
    }
}
