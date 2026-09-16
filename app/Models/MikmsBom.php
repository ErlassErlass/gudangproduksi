<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MikmsBom extends Model
{
    use HasFactory;

    protected $table = 'mikms_bom';

    protected $fillable = [
        'module_id',
        'item_id',
        'box_category',
        'quantity',
        'notes',
    ];

    public function module()
    {
        return $this->belongsTo(MikmsModule::class, 'module_id');
    }

    public function item()
    {
        return $this->belongsTo(Item::class, 'item_id');
    }
}
