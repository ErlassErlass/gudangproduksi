<?php

namespace App\Domains\Inventory\Models;

use App\Domains\Inventory\Enums\ReservationStatus;
use App\Domains\MasterData\Models\Product;
use App\Domains\Warehouse\Models\Location;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property string $reservation_no
 * @property string $source_type
 * @property int $source_id
 * @property int $product_id
 * @property int $location_id
 * @property int|null $batch_id
 * @property int|null $product_serial_id
 * @property string $quantity
 * @property string $consumed_qty
 * @property ReservationStatus $status
 */
class InventoryReservation extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'status' => ReservationStatus::class,
            'quantity' => 'decimal:4',
            'consumed_qty' => 'decimal:4',
            'expires_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Product, $this> */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /** @return BelongsTo<Location, $this> */
    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }
}
