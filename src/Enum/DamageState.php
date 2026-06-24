<?php
namespace App\Enum;
enum DamageState: string {
    case None = 'none';
    case ArmorOnly = 'armor_only';
    case Structural = 'structural';
    case Crippled = 'crippled';
    case Destroyed = 'destroyed';
}
