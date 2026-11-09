<?php

namespace App\Domains\Inventory\Enums;

enum ReservationStatus: string
{
    case ACTIVE = 'ACTIVE';
    case PARTIAL = 'PARTIAL';
    case RELEASED = 'RELEASED';
    case CONSUMED = 'CONSUMED';
    case EXPIRED = 'EXPIRED';
}
