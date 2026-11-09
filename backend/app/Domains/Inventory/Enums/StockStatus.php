<?php

namespace App\Domains\Inventory\Enums;

/**
 * Stock condition / availability bucket (agent_docs/01_PRD.md FR-09, 06_BUSINESS_RULES.md #6–#7).
 * Only `AVAILABLE` is eligible for normal picking/issue.
 */
enum StockStatus: string
{
    case AVAILABLE = 'AVAILABLE';
    case QUARANTINE = 'QUARANTINE';
    case DAMAGED = 'DAMAGED';
    case IN_TRANSIT = 'IN_TRANSIT';
    case AT_VENDOR = 'AT_VENDOR';
    case MISSING = 'MISSING';

    public function isPickable(): bool
    {
        return $this === self::AVAILABLE;
    }
}
