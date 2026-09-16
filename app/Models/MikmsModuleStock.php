<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MikmsModuleStock extends Model
{
    protected $fillable = [
        'module_id',
        'stock_ready',
    ];

    protected $casts = [
        'stock_ready' => 'integer',
    ];

    public function module(): BelongsTo
    {
        return $this->belongsTo(MikmsModule::class, 'module_id');
    }

    public function logs(): HasMany
    {
        return $this->hasMany(MikmsModuleStockLog::class, 'module_id', 'module_id');
    }
}
