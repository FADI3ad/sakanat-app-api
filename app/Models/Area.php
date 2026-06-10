<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Guarded;
use Illuminate\Database\Eloquent\Model;
#[Guarded(['id'])]
class Area extends Model
{
    


    public function printServices()
    {
        return $this->hasMany(PrintService::class);
    }
}
