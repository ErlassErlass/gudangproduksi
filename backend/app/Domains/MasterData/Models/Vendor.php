<?php

namespace App\Domains\MasterData\Models;

use App\Domains\Warehouse\Models\Location;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Vendor extends Model
{
    protected $fillable = [
        'code', 'name', 'vendor_type', 'contact_name', 'phone', 'address_json', 'default_location_id', 'is_active',
    ];

    protected function casts(): array
    {
        return ['address_json' => 'array', 'is_active' => 'boolean'];
    }

    /** @return BelongsTo<Location, $this> */
    public function defaultLocation(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'default_location_id');
    }
}
