<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MikmsModule extends Model
{
    use HasFactory;

    protected $table = 'mikms_modules';

    protected $fillable = [
        'code',
        'name',
        'description',
    ];

    public function boms()
    {
        return $this->hasMany(MikmsBom::class, 'module_id');
    }

    public function productions()
    {
        return $this->hasMany(MikmsProduction::class, 'module_id');
    }

    public function qcLogs()
    {
        return $this->hasMany(MikmsQcLog::class, 'module_id');
    }
}
