<?php

namespace App\Domains\Production\Enums;

enum BomVersionStatus: string
{
    case DRAFT = 'DRAFT';
    case REVIEW = 'REVIEW';
    case ACTIVE = 'ACTIVE';
    case RETIRED = 'RETIRED';
}
