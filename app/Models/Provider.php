<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\Guarded;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Guarded(['id'])]
class Provider extends Model
{




    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function printServices()
    {
        return $this->hasMany(PrintService::class);
    }


}
