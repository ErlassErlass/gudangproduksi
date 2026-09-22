<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MikmsQcChecklistTemplate extends Model
{
    protected $table = 'mikms_qc_checklist_templates';

    protected $fillable = [
        'product_id', 'check_item', 'sort_order', 'active',
    ];

    protected $casts = [
        'active' => 'boolean',
    ];

    public function product()
    {
        return $this->belongsTo(MikmsFinishGood::class, 'product_id');
    }
}
