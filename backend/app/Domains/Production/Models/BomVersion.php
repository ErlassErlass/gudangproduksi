<?php

namespace App\Domains\Production\Models;

use App\Domains\Production\Enums\BomVersionStatus;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $bom_header_id
 * @property int $version_no
 * @property BomVersionStatus $status
 * @property \Illuminate\Support\Carbon|null $effective_from
 * @property \Illuminate\Support\Carbon|null $effective_until
 */
class BomVersion extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'status' => BomVersionStatus::class,
            'effective_from' => 'date',
            'effective_until' => 'date',
            'approved_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<BomHeader, $this> */
    public function header(): BelongsTo
    {
        return $this->belongsTo(BomHeader::class, 'bom_header_id');
    }

    /** @return HasMany<BomItem, $this> */
    public function items(): HasMany
    {
        return $this->hasMany(BomItem::class)->orderBy('sequence');
    }

    /** @return BelongsTo<User, $this> */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
