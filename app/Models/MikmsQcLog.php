<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MikmsQcLog extends Model
{
    use HasFactory;

    protected $table = 'mikms_qc_logs';

    protected $fillable = [
        'qc_date',
        'module_id',
        'target_box_code',
        'status_qc',
        'defect_notes',
        'checked_by',
        'notes',
    ];

    public function module()
    {
        return $this->belongsTo(MikmsModule::class, 'module_id');
    }
}
