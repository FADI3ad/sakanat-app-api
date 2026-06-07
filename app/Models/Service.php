<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Sluggable\Attributes\Sluggable;


#[Sluggable(from: 'title', to: 'slug')]
class Service extends Model
{
        





}
