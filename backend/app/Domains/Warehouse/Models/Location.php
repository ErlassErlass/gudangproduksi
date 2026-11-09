<?php

namespace App\Domains\Warehouse\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Hierarchical storage location (building → floor → room → rack → bin) or external vendor/customer
 * location (agent_docs/05_DATABASE_SCHEMA.md §5). Rack zoning is master data, never hardcoded.
 *
 * @property int $id
 * @property int $warehouse_id
 * @property int|null $parent_id
 * @property string $code
 * @property string|null $barcode
 * @property string $name
 * @property string $location_type
 * @property bool $is_external
 * @property bool $is_active
 */
class Location extends Model
{
    protected $fillable = [
        'warehouse_id', 'parent_id', 'code', 'barcode', 'name', 'location_type', 'is_external', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_external' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    /** @return BelongsTo<Warehouse, $this> */
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    /** @return BelongsTo<self, $this> */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    /** @return HasMany<self, $this> */
    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }
}
