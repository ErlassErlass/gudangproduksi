<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MikmsDispatch extends Model
{
    protected $table = 'mikms_dispatches';

    protected $fillable = [
        'dispatch_date', 'unit_id', 'type',
        'destination', 'dispatched_by', 'notes', 'returned_at',
    ];

    protected $casts = [
        'dispatch_date' => 'date',
        'returned_at'   => 'datetime',
    ];

    public function unit()
    {
        return $this->belongsTo(MikmsUnit::class, 'unit_id');
    }

    public function isReturned(): bool
    {
        return !is_null($this->returned_at);
    }
}
