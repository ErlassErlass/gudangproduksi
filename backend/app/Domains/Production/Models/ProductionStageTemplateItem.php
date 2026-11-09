<?php

namespace App\Domains\Production\Models;

use App\Domains\MasterData\Models\Product;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductionStageTemplateItem extends Model
{
    protected $fillable = ['stage_template_id', 'sequence', 'stage_code', 'name', 'output_product_id', 'qc_required'];

    protected function casts(): array
    {
        return ['qc_required' => 'boolean'];
    }

    /** @return BelongsTo<Product, $this> */
    public function outputProduct(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'output_product_id');
    }
}
