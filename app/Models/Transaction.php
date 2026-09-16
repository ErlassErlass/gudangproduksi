<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Transaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'item_id',
        'asset_id',
        'vendor_id',
        'customer_id',
        'location_id',
        'tipe',
        'tipe_detail',
        'qty',
        'transaction_date',
        'no_dokumen',
        'no_po',
        'no_prn',
        'job_number',
        'transfer_order',
        'sumber',
        'penerima',
        'keperluan',
        'lokasi',
        'petugas',
        'catatan',
        'client_id',
    ];

    protected $casts = [
        'transaction_date' => 'date',
        'qty' => 'integer',
    ];

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class)->withTrashed();
    }

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
