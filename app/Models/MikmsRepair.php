<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MikmsRepair extends Model
{
    use HasFactory;

    protected $table = 'mikms_repairs';

    protected $fillable = [
        'repair_date',
        'item_code',
        'item_name',
        'asset_id',
        'damage_type',
        'repair_action',
        'quantity',
        'repair_result',
        'repaired_by',
        'notes',
    ];
}
