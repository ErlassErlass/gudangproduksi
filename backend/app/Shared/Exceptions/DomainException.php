<?php

namespace App\Shared\Exceptions;

use RuntimeException;

/**
 * A business rule or state-transition violation. Rendered by the API as
 * `{ message, code, errors }` with HTTP 409 by default (agent_docs/08_API_SPECIFICATION.md).
 */
class DomainException extends RuntimeException
{
    /**
     * @param  array<string, list<string>>  $errors
     */
    public function __construct(
        string $message,
        private readonly string $errorCode = 'DOMAIN_RULE_VIOLATION',
        private readonly int $status = 409,
        private readonly array $errors = [],
    ) {
        parent::__construct($message);
    }

    public function errorCode(): string
    {
        return $this->errorCode;
    }

    public function status(): int
    {
        return $this->status;
    }

    /** @return array<string, list<string>> */
    public function errors(): array
    {
        return $this->errors;
    }
}
