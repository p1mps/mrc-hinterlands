<?php
namespace App\Enum;
enum ContractType: string {
    case Expedition = 'expedition';
    case Garrison = 'garrison';
    case Raid = 'raid';
    case Retainer = 'retainer';
    case Invasion = 'invasion';
    case Liaison = 'liaison';
}
