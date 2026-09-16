<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MikmsProduction extends Model
{
    use HasFactory;

    protected $table = 'mikms_productions';

    protected $fillable = [
        'production_date',
        'module_id',
        'quantity_produced',
        'produced_by',
        'notes',
    ];

    public function module()
    {
        return $this->belongsTo(MikmsModule::class, 'module_id');
    }
}
