<?php

namespace App\Domains\MasterData\Enums;

/** Independent product classification dimension #3 (agent_docs/01_PRD.md FR-03). */
enum FulfillmentType: string
{
    case SELL = 'SELL';
    case RENTAL = 'RENTAL';
    case INTERNAL = 'INTERNAL';
    case MIXED = 'MIXED';
}
