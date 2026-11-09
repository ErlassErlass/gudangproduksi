<?php

namespace App\Shared\Enums;

/** Standard document lifecycle states (agent_docs/06_BUSINESS_RULES.md "Document lifecycle"). */
enum DocumentStatus: string
{
    case DRAFT = 'DRAFT';
    case SUBMITTED = 'SUBMITTED';
    case APPROVED = 'APPROVED';
    case REJECTED = 'REJECTED';
    case POSTED = 'POSTED';
    case REVERSED = 'REVERSED';
    case CLOSED = 'CLOSED';
    case CANCELLED = 'CANCELLED';

    public function isMutable(): bool
    {
        return $this === self::DRAFT;
    }
}
