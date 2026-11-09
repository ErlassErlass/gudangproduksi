<?php

namespace App\Domains\MasterData\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $product_id
 * @property string $batch_number
 */
class ProductBatch extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'manufactured_at' => 'date',
            'expiry_at' => 'date',
        ];
    }

    /** @return BelongsTo<Product, $this> */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
