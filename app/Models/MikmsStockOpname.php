<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MikmsStockOpname extends Model
{
    use HasFactory;

    protected $table = 'mikms_stock_opnames';

    protected $fillable = [
        'opname_date',
        'item_code',
        'item_name',
        'system_quantity',
        'physical_quantity',
        'difference',
        'difference_reason',
        'counted_by',
    ];
}
