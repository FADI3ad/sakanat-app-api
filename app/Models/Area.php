<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Guarded;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Guarded(['id'])]
class Area extends Model
{
    /**
     * Get all services available in this area.
     */
    public function services(): HasMany
    {
        return $this->hasMany(Service::class);
    }
}
