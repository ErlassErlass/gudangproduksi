<?php

namespace App\Domains\MasterData\Models;

use App\Domains\MasterData\Enums\FulfillmentType;
use App\Domains\MasterData\Enums\PhysicalType;
use App\Domains\MasterData\Enums\ProductType;
use App\Domains\MasterData\Enums\TrackingMethod;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $code
 * @property string|null $legacy_code
 * @property string $name
 * @property string|null $description
 * @property int|null $category_id
 * @property ProductType $product_type
 * @property PhysicalType $physical_type
 * @property FulfillmentType $fulfillment_type
 * @property TrackingMethod $tracking_method
 * @property int $base_uom_id
 * @property string $safety_stock_qty
 * @property string|null $reference_purchase_price
 * @property bool $is_manufactured
 * @property bool $is_purchasable
 * @property bool $is_sellable
 * @property bool $is_rentable
 * @property string $status
 */
class Product extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'product_type' => ProductType::class,
            'physical_type' => PhysicalType::class,
            'fulfillment_type' => FulfillmentType::class,
            'tracking_method' => TrackingMethod::class,
            'safety_stock_qty' => 'decimal:4',
            'reference_purchase_price' => 'decimal:2',
            'is_manufactured' => 'boolean',
            'is_purchasable' => 'boolean',
            'is_sellable' => 'boolean',
            'is_rentable' => 'boolean',
        ];
    }

    /** @return BelongsTo<ProductCategory, $this> */
    public function category(): BelongsTo
    {
        return $this->belongsTo(ProductCategory::class, 'category_id');
    }

    /** @return BelongsTo<Uom, $this> */
    public function baseUom(): BelongsTo
    {
        return $this->belongsTo(Uom::class, 'base_uom_id');
    }

    /** @return HasMany<ProductBarcode, $this> */
    public function barcodes(): HasMany
    {
        return $this->hasMany(ProductBarcode::class);
    }

    /** @return HasMany<ProductSerial, $this> */
    public function serials(): HasMany
    {
        return $this->hasMany(ProductSerial::class);
    }

    /** @return HasMany<ProductBatch, $this> */
    public function batches(): HasMany
    {
        return $this->hasMany(ProductBatch::class);
    }

    /** @return HasMany<ProductComponent, $this> */
    public function components(): HasMany
    {
        return $this->hasMany(ProductComponent::class, 'parent_product_id');
    }

    /** @return HasMany<ProductComponent, $this> */
    public function parentBundles(): HasMany
    {
        return $this->hasMany(ProductComponent::class, 'component_product_id');
    }

    /**
     * Only physical items with a tracking method may participate in inventory ledger movements
     * (agent_docs/AGENT_INSTRUCTIONS.md §8 "No treating program/service as ordinary stock item").
     */
    public function isStockTracked(): bool
    {
        return $this->physical_type === PhysicalType::PHYSICAL
            && $this->tracking_method !== TrackingMethod::NONE
            && $this->product_type === ProductType::PRODUCT;
    }

    public function barcodePrints(): HasMany
    {
        return $this->hasMany(ProductBarcodePrint::class);
    }
}
