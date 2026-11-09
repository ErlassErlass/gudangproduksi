<?php

namespace App\Domains\Production\Models;

use App\Domains\MasterData\Models\Product;
use App\Domains\Production\Enums\BomType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BomHeader extends Model
{
    protected $fillable = ['code', 'product_id', 'name', 'bom_type', 'stage_template_id', 'status'];

    protected function casts(): array
    {
        return ['bom_type' => BomType::class];
    }

    /** @return BelongsTo<Product, $this> */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /** @return BelongsTo<ProductionStageTemplate, $this> */
    public function stageTemplate(): BelongsTo
    {
        return $this->belongsTo(ProductionStageTemplate::class, 'stage_template_id');
    }

    /** @return HasMany<BomVersion, $this> */
    public function versions(): HasMany
    {
        return $this->hasMany(BomVersion::class)->orderByDesc('version_no');
    }

    /** @return \Illuminate\Database\Eloquent\Relations\HasOne<BomVersion, $this> */
    public function activeVersion(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(BomVersion::class)->where('status', \App\Domains\Production\Enums\BomVersionStatus::ACTIVE->value);
    }
}
