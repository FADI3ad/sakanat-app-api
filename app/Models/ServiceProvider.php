<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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



}
