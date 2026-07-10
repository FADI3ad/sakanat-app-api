<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\Guarded;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Guarded(['id'])]
class ContactMessage extends Model
{
    /**
     * Get the user who sent the contact message.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
