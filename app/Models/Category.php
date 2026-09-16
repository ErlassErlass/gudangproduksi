<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    use HasFactory;

    protected $fillable = [
        'nama',
        'parent_id',
    ];

    /**
     * Kategori induk (Sub Kategori dari).
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    /**
     * Sub-kategori yang dimiliki oleh kategori ini.
     */
    public function children(): HasMany
    {
        return $this->hasMany(Category::class, 'parent_id');
    }

    /**
     * Barang yang termasuk dalam kategori ini.
     */
    public function items(): HasMany
    {
        return $this->hasMany(Item::class);
    }
}
