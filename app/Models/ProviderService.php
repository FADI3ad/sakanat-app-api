<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class ProviderService extends Pivot
{
    protected $table = 'provider_service';

    protected $casts = [
        'properties' => 'array',
    ];

    public function service()
    {
        return $this->belongsTo(Service::class);
    }

    public function provider()
    {
        return $this->belongsTo(Provider::class, 'provider_id');
    }
}
