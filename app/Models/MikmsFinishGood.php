<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MikmsFinishGood extends Model
{
    protected $table = 'mikms_finish_goods';

    protected $fillable = [
        'code', 'name', 'type', 'can_rent', 'active', 'description',
    ];

    protected $casts = [
        'can_rent' => 'boolean',
        'active'   => 'boolean',
    ];

    public function units()
    {
        return $this->hasMany(MikmsUnit::class, 'product_id');
    }

    public function qcTemplates()
    {
        return $this->hasMany(MikmsQcChecklistTemplate::class, 'product_id')
                    ->where('active', true)
                    ->orderBy('sort_order');
    }

    public function directInputs()
    {
        return $this->hasMany(MikmsDirectInput::class, 'product_id');
    }

    /**
     * Hitung stok in_stock real-time
     */
    public function stockCount(): int
    {
        return $this->units()->where('status', 'in_stock')->count();
    }

    /**
     * Generate serial number berikutnya untuk produk ini
     * Format: MLK-00001 (global sequential per product code)
     */
    public function nextSerial(): string
    {
        $last = MikmsUnit::where('product_id', $this->id)
                         ->orderBy('id', 'desc')
                         ->value('serial_number');

        if (!$last) {
            $next = 1;
        } else {
            // Extract nomor dari format CODE-00001
            $parts = explode('-', $last);
            $next  = (int) end($parts) + 1;
        }

        return $this->code . '-' . str_pad($next, 5, '0', STR_PAD_LEFT);
    }
}
