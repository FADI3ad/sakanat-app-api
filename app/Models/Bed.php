<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Guarded;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Guarded(['id'])]
class Bed extends Model
{
    /**
     * Get the room this bed belongs to.
     */
    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }
}
