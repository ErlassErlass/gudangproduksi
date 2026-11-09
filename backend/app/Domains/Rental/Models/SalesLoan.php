<?php

namespace App\Domains\Rental\Models;

use App\Domains\MasterData\Models\Customer;
use App\Domains\MasterData\Models\Product;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalesLoan extends Model
{
    protected $fillable = [
        'loan_no',
        'group_leader',
        'sales_name',
        'customer_id',
        'product_id',
        'quantity',
        'loan_date',
        'return_date',
        'status',
        'notes',
        'return_notes',
        'receipt_no',
        'returned_at',
    ];

    protected function casts(): array
    {
        return [
            'loan_date' => 'date:Y-m-d',
            'return_date' => 'date:Y-m-d',
            'returned_at' => 'datetime',
            'quantity' => 'decimal:4',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
