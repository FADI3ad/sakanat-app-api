<?php

namespace App\Models;

use App\Enums\UtilityTypeEnum;
use Illuminate\Database\Eloquent\Attributes\Guarded;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Guarded(['id'])]
class UtilityBill extends Model
{
    /**
     * Get the property this bill belongs to.
     */
    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type'    => UtilityTypeEnum::class,
            'is_paid' => 'boolean',
            'paid_at' => 'datetime',
            'amount'  => 'decimal:2',
        ];
    }
}
