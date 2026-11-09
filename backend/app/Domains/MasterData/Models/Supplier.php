<?php

namespace App\Domains\MasterData\Models;

use Illuminate\Database\Eloquent\Model;

class Supplier extends Model
{
    protected $fillable = ['code', 'name', 'contact_name', 'phone', 'email', 'address_json', 'is_active'];

    protected function casts(): array
    {
        return ['address_json' => 'array', 'is_active' => 'boolean'];
    }
}
