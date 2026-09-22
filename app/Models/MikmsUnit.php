<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MikmsUnit extends Model
{
    protected $table = 'mikms_units';

    protected $fillable = [
        'serial_number', 'product_id', 'parent_unit_id', 'status',
        'source_type', 'source_id', 'notes',
    ];

    public function product()
    {
        return $this->belongsTo(MikmsFinishGood::class, 'product_id');
    }

    public function parent()
    {
        return $this->belongsTo(MikmsUnit::class, 'parent_unit_id');
    }

    public function children()
    {
        return $this->hasMany(MikmsUnit::class, 'parent_unit_id')->with(['product', 'latestQc']);
    }

    public function qcLogs()
    {
        return $this->hasMany(MikmsQcLog::class, 'unit_id')->latest();
    }

    public function latestQc()
    {
        return $this->hasOne(MikmsQcLog::class, 'unit_id')->latest();
    }

    public function dispatches()
    {
        return $this->hasMany(MikmsDispatch::class, 'unit_id');
    }

    public function activeDispatch()
    {
        return $this->hasOne(MikmsDispatch::class, 'unit_id')
                    ->whereNull('returned_at')
                    ->latest();
    }

    /**
     * Label status dalam Bahasa Indonesia
     */
    public function statusLabel(): string
    {
        return match($this->status) {
            'qc_pending' => 'Menunggu QC',
            'in_stock'   => 'Stok Gudang',
            'bundled'    => 'Terbundling',
            'dispatched' => 'Terkirim',
            'returned'   => 'Dikembalikan',
            'repair'     => 'Perbaikan',
            'disposed'   => 'Dilepas',
            default      => $this->status,
        };
    }

    /**
     * Badge color untuk UI
     */
    public function statusBadge(): string
    {
        return match($this->status) {
            'qc_pending' => 'badge-warning',
            'in_stock'   => 'badge-success',
            'bundled'    => 'badge-primary',
            'dispatched' => 'badge-info',
            'returned'   => 'badge-secondary',
            'repair'     => 'badge-error',
            'disposed'   => 'badge-ghost',
            default      => 'badge-neutral',
        };
    }
}
