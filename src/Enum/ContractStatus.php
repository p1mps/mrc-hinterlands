<?php
namespace App\Enum;
enum ContractStatus: string {
    case Available = 'available';
    case Active = 'active';
    case Completed = 'completed';
    case Broken = 'broken';
}
