<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MikmsDirectInput extends Model
{
    protected $table = 'mikms_direct_inputs';

    protected $fillable = [
        'input_date', 'product_id', 'quantity',
        'supplier', 'received_by', 'notes',
    ];

    protected $casts = [
        'input_date' => 'date',
    ];

    public function product()
    {
        return $this->belongsTo(MikmsFinishGood::class, 'product_id');
    }

    public function units()
    {
        return $this->hasMany(MikmsUnit::class, 'source_id')
                    ->where('source_type', 'direct_input');
    }
}
