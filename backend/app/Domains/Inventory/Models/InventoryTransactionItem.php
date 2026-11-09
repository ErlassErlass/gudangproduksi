<?php

namespace App\Domains\Inventory\Models;

use App\Domains\MasterData\Models\Product;
use App\Domains\MasterData\Models\ProductBatch;
use App\Domains\MasterData\Models\ProductSerial;
use App\Domains\Warehouse\Models\Location;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $inventory_transaction_id
 * @property int $product_id
 * @property int|null $from_location_id
 * @property int|null $to_location_id
 * @property int|null $batch_id
 * @property int|null $product_serial_id
 * @property string|null $stock_status_from
 * @property string|null $stock_status_to
 * @property string $quantity
 * @property string|null $unit_cost
 * @property string|null $total_cost
 */
class InventoryTransactionItem extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:4',
            'unit_cost' => 'decimal:6',
            'total_cost' => 'decimal:2',
        ];
    }

    /** @return BelongsTo<InventoryTransaction, $this> */
    public function transaction(): BelongsTo
    {
        return $this->belongsTo(InventoryTransaction::class, 'inventory_transaction_id');
    }

    /** @return BelongsTo<Product, $this> */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /** @return BelongsTo<Location, $this> */
    public function fromLocation(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'from_location_id');
    }

    /** @return BelongsTo<Location, $this> */
    public function toLocation(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'to_location_id');
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
