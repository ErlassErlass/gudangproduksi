<?php

namespace App\Domains\MasterData\Enums;

/** Independent product classification dimension #4 (agent_docs/01_PRD.md FR-03). */
enum TrackingMethod: string
{
    case QUANTITY = 'QUANTITY';
    case SERIALIZED = 'SERIALIZED';
    case BATCH = 'BATCH';
    case NONE = 'NONE';
}
