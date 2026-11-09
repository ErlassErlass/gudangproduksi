<?php

namespace App\Domains\Production\Models;

use App\Domains\MasterData\Models\Product;
use App\Domains\MasterData\Models\Uom;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $bom_version_id
 * @property int|null $parent_bom_item_id
 * @property int $component_product_id
 * @property string $item_type
 * @property string $quantity
 * @property int $uom_id
 * @property string $scrap_rate
 * @property int $sequence
 */
class BomItem extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:4',
            'scrap_rate' => 'decimal:4',
        ];
    }

    /** @return BelongsTo<Product, $this> */
    public function component(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'component_product_id');
    }

    /** @return BelongsTo<Uom, $this> */
    public function uom(): BelongsTo
    {
        return $this->belongsTo(Uom::class);
    }

    /** @return HasMany<self, $this> */
    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_bom_item_id')->orderBy('sequence');
    }

    /** @return BelongsTo<BomVersion, $this> */
    public function bomVersion(): BelongsTo
    {
        return $this->belongsTo(BomVersion::class, 'bom_version_id');
    }
}
