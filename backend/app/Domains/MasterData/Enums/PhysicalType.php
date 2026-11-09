<?php

namespace App\Domains\MasterData\Enums;

/** Independent product classification dimension #2 (agent_docs/01_PRD.md FR-03). */
enum PhysicalType: string
{
    case PHYSICAL = 'PHYSICAL';
    case DIGITAL = 'DIGITAL';
    case NON_INVENTORY = 'NON_INVENTORY';
}
