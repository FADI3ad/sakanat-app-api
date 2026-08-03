<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Guarded;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

#[Guarded(['id'])]
class Property extends Model
{
    /**
     * Get the property_owner user who owns this property.
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get all utility bills for this property.
     */
    public function utilityBills(): HasMany
    {
        return $this->hasMany(UtilityBill::class);
    }

    /**
     * Get all rooms inside this property.
     */
    public function rooms(): HasMany
    {
        return $this->hasMany(Room::class);
    }

    /**
     * Get all attendance logs for this property.
     */
    public function attendanceLogs(): HasMany
    {
        return $this->hasMany(AttendanceLog::class);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_available' => 'boolean',
            'latitude'     => 'float',
            'longitude'    => 'float',
            'radius'       => 'float',
            'curfew_time'  => 'string',
        ];
    }
}
