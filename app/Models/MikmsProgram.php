<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MikmsProgram extends Model
{
    use HasFactory;

    protected $table = 'mikms_programs';

    protected $fillable = [
        'code',
        'name',
        'description',
        'modules',
        'total_pcs',
        'is_active',
    ];

    protected $casts = [
        'modules' => 'array',
        'is_active' => 'boolean',
        'total_pcs' => 'integer',
    ];

    /**
     * Compute total components from BOM of assigned modules.
     */
    public function calculateTotalPcs(): int
    {
        if (empty($this->modules)) {
            return 0;
        }

        $moduleIds = MikmsModule::whereIn('code', $this->modules)->pluck('id');
        return (int) MikmsBom::whereIn('module_id', $moduleIds)->sum('quantity');
    }
}
