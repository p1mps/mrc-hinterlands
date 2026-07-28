<?php

namespace App\Enum;

enum ContractLogEntryType: string
{
    case BasePay = 'Base Pay';
    case TrackSetup = 'Track Setup';
    case PostTrack = 'Post Track';
    case Downtime = 'Downtime';
    case Salvage = 'Salvage';
    case Transport = 'transport';
    case Maintenance = 'maintenance';
}
