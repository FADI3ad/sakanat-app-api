<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Guarded;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Guarded(['id'])]
class Type extends Model
{
    /**
     * Get the services associated with this type.
     */
    public function services(): HasMany
    {
        return $this->hasMany(Service::class);
    }
}