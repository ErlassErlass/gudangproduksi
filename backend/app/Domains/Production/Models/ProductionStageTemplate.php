<?php

namespace App\Domains\Production\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductionStageTemplate extends Model
{
    protected $fillable = ['code', 'name', 'product_scope', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    /** @return HasMany<ProductionStageTemplateItem, $this> */
    public function items(): HasMany
    {
        return $this->hasMany(ProductionStageTemplateItem::class, 'stage_template_id')->orderBy('sequence');
    }
}
