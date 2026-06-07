<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Fillable(['address','user_id'])]
class ServiceProvider extends Model
{
    /**
     * Get the user that owns the ServiceProvider profile.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * The services that belong to the ServiceProvider.
     */
    public function services(): BelongsToMany
    {
        return $this->belongsToMany(Service::class, 'service_provider_service');
    }
}
