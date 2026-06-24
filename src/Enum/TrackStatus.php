<?php
namespace App\Enum;
enum TrackStatus: string {
    case Pending   = 'pending';
    case Completed = 'completed';
}
