<?php

namespace App\Domains\MasterData\Models;

use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    protected $fillable = [
        'code', 'name', 'sales_name', 'customer_type', 'school_name', 'contact_name', 'phone', 'email', 'address_json', 'is_active',
    ];

    protected function casts(): array
    {
        return ['address_json' => 'array', 'is_active' => 'boolean'];
    }
}
