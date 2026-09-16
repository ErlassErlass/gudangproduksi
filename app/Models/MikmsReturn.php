<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MikmsReturn extends Model
{
    use HasFactory;

    protected $table = 'mikms_returns';

    protected $fillable = [
        'return_date',
        'box_code',
        'school_name',
        'condition',
        'problematic_item_code',
        'problematic_item_name',
        'problematic_quantity',
        'received_by',
        'notes',
    ];
}
