<?php

namespace App\Shared\Idempotency;

use App\Shared\Exceptions\DomainException;
use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @property int $id
 * @property string $key
 * @property string $operation
 * @property string $request_hash
 * @property int|null $response_status
 * @property array|null $response_json
 */
class IdempotencyKey extends Model
{
    public const UPDATED_AT = null;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'response_json' => 'array',
            'created_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }
}
