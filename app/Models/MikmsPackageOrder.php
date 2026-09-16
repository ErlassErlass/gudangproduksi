<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MikmsPackageOrder extends Model
{
    protected $fillable = [
        'order_date',
        'program_code',
        'program_name',
        'package_qty',
        'customer_id',
        'customer_name',
        'status',
        'deduction_log',
        'ordered_by',
        'notes',
    ];

    protected $casts = [
        'order_date' => 'date',
        'package_qty' => 'integer',
        'deduction_log' => 'array',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }
}
