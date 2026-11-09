<?php

namespace App\Domains\Inventory\Models;

use App\Domains\Inventory\Enums\TransactionType;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Immutable ledger header once posted. Corrections use a linked reversal transaction.
 *
 * @property int $id
 * @property string $document_no
 * @property TransactionType $transaction_type
 * @property string $status
 * @property \Illuminate\Support\Carbon $transaction_date
 * @property string $source_document_type
 * @property int $source_document_id
 * @property string $idempotency_key
 * @property int|null $posted_by
 * @property \Illuminate\Support\Carbon|null $posted_at
 * @property int|null $reversed_transaction_id
 * @property string|null $notes
 */
class InventoryTransaction extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'transaction_type' => TransactionType::class,
            'transaction_date' => 'datetime',
            'posted_at' => 'datetime',
        ];
    }

    /** @return HasMany<InventoryTransactionItem, $this> */
    public function items(): HasMany
    {
        return $this->hasMany(InventoryTransactionItem::class);
    }

    /** @return BelongsTo<User, $this> */
    public function poster(): BelongsTo
    {
        return $this->belongsTo(User::class, 'posted_by');
    }

    /** @return BelongsTo<self, $this> */
    public function reversedTransaction(): BelongsTo
    {
        return $this->belongsTo(self::class, 'reversed_transaction_id');
    }
}
