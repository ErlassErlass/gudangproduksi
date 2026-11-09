<?php

namespace App\Domains\MasterData\Models;

use App\Models\User;
use App\Domains\Inventory\Models\InventoryTransaction;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductBarcodePrint extends Model
{
    protected $fillable = [
        'product_id',
        'user_id',
        'batch_no',
        'barcode',
        'quantity',
        'printed_at',
        'reprint_count',
        'last_reprinted_at',
        'inventory_transaction_id',
        'notes',
    ];

    protected $casts = [
        'printed_at' => 'datetime',
        'last_reprinted_at' => 'datetime',
        'quantity' => 'integer',
        'reprint_count' => 'integer',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(InventoryTransaction::class, 'inventory_transaction_id');
    }
}
