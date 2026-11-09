<?php

namespace App\Domains\MasterData\Enums;

/** Independent product classification dimension #1 (agent_docs/01_PRD.md FR-03). */
enum ProductType: string
{
    case PRODUCT = 'PRODUCT';
    case SERVICE = 'SERVICE';
    case PROGRAM = 'PROGRAM';
}
