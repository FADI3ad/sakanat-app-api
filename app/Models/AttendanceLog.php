<?php

namespace App\Models;

use App\Enums\AttendanceStatusEnum;
use Illuminate\Database\Eloquent\Attributes\Guarded;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Guarded(['id'])]
class AttendanceLog extends Model
{
    /**
     * Get the property this log belongs to.
     */
    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    /**
     * Get the bed (and thereby the resident) for this log.
     */
    public function bed(): BelongsTo
    {
        return $this->belongsTo(Bed::class);
    }

    /**
     * Get the resident (user) for this log.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'date'             => 'date',
            'status'           => AttendanceStatusEnum::class,
            'checked_in_at'    => 'datetime',
            'scanned_latitude' => 'float',
            'scanned_longitude'=> 'float',
            'distance_from_property' => 'float',
        ];
    }
}
