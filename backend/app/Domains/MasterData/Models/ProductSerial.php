<?php

namespace App\Domains\MasterData\Models;

use App\Domains\Warehouse\Models\Location;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $product_id
 * @property string $serial_number
 * @property string|null $barcode
 * @property int|null $current_location_id
 * @property string $current_status
 * @property string|null $acquisition_cost
 */
class ProductSerial extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'acquisition_cost' => 'decimal:6',
            'acquired_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Product, $this> */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /** @return BelongsTo<Location, $this> */
    public function currentLocation(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'current_location_id');
    }
}
