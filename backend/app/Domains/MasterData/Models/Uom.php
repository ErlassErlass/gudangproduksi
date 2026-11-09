<?php

namespace App\Domains\MasterData\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $code
 * @property string $name
 * @property string $dimension
 * @property bool $is_active
 */
class Uom extends Model
{
    protected $fillable = ['code', 'name', 'dimension', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }
}
