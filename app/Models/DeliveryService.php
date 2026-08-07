<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Guarded;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Guarded(['id'])]
class DeliveryService extends Model
{
    /**
     * Get the service type that owns this delivery service.
     */
    public function type(): BelongsTo
    {
        return $this->belongsTo(Type::class);
    }
}
