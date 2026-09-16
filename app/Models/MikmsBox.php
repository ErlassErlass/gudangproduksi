<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MikmsBox extends Model
{
    use HasFactory;

    protected $table = 'mikms_boxes';

    protected $fillable = [
        'box_code',
        'category',
        'program_code',
        'status',
        'notes',
    ];
}
