<?php

namespace App\Enum;

enum ContractLogEntryType: string
{
    case BasePay = 'base_pay';
    case TrackSetup = 'track_setup';
    case PostTrack = 'post_track';
    case Downtime = 'downtime';
    case Salvage = 'salvage';
    case Transport = 'transport';
      case Maintenance = 'maintenance';
      case Breach = 'breach';
      case Negotiation = 'negotiation';
      case MonthAdvance = 'month_advance';
}
