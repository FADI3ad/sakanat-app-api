<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Guarded;
use Illuminate\Database\Eloquent\Model;
use Spatie\Sluggable\Attributes\Sluggable;
use Spatie\Sluggable\HasSlug;


#[Sluggable(from: 'title', to: 'slug')]
#[Guarded(['id'])]
class Service extends Model
{



    public function getRouteKeyName(): string
    {
        return 'slug';
    }

}
