<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MikmsShipment extends Model
{
    use HasFactory;

    protected $table = 'mikms_shipments';

    protected $fillable = [
        'shipment_date',
        'box_code',
        'program_code',
        'program_name',
        'school_name',
        'quantity_box',
        'shipped_by',
        'received_by_school',
        'notes',
    ];
}
