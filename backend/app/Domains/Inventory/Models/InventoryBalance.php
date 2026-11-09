<?php

namespace App\Domains\Inventory\Models;

use App\Domains\Inventory\Enums\StockStatus;
use App\Domains\MasterData\Models\Product;
use App\Domains\MasterData\Models\ProductBatch;
use App\Domains\MasterData\Models\ProductSerial;
use App\Domains\Warehouse\Models\Location;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Projection of stock balance for one logical dimension.
 * Must NEVER be mutated outside `InventoryPostingService` and `ReservationService`.
 *
 * @property int $id
 * @property string $dimension_key
 * @property int $product_id
 * @property int $location_id
 * @property int|null $batch_id
 * @property int|null $product_serial_id
 * @property StockStatus $stock_status
 * @property string $on_hand_qty
 * @property string $reserved_qty
 * @property string $avg_unit_cost
 * @property int $row_version
 */
class InventoryBalance extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'stock_status' => StockStatus::class,
            'on_hand_qty' => 'decimal:4',
            'reserved_qty' => 'decimal:4',
            'avg_unit_cost' => 'decimal:6',
        ];
    }

    public static function buildDimensionKey(
        int $productId,
        int $locationId,
        ?int $batchId,
        ?int $serialId,
        StockStatus|string $status,
    ): string {
        $statusCode = $status instanceof StockStatus ? $status->value : $status;

        return sprintf('%d:%d:%d:%d:%s', $productId, $locationId, $batchId ?? 0, $serialId ?? 0, $statusCode);
    }

    /** Available = eligible on-hand − active reservations (06_BUSINESS_RULES.md #6–#7). */
    public function availableQty(): string
    {
        if (! $this->stock_status->isPickable()) {
            return '0.0000';
        }

        return bcsub($this->on_hand_qty, $this->reserved_qty, 4);
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

    /** @return BelongsTo<ProductBatch, $this> */
    public function batch(): BelongsTo
    {
        return $this->belongsTo(ProductBatch::class, 'batch_id');
    }

    /** @return BelongsTo<ProductSerial, $this> */
    public function serial(): BelongsTo
    {
        return $this->belongsTo(ProductSerial::class, 'product_serial_id');
    }
}
