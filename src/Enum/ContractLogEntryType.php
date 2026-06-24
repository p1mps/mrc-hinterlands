<?php
namespace App\Enum;
enum ContractLogEntryType: string {
    case Transport   = 'transport';
    case Maintenance = 'maintenance';
    case BasePay     = 'base_pay';
    case TrackSetup  = 'track_setup';
    case PostTrack   = 'post_track';
    case Downtime    = 'downtime';
    case Note        = 'note';
}
