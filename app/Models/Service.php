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

    /**
     * Map each service slug to its specific model class.
     * When a new service type is added, register its model here.
     */
    public static function serviceModelMap(): array
    {
        return [
            \Illuminate\Support\Str::slug('خدمات الطباعة والتصوير') => PrintService::class,
        ];
    }

    /**
     * Resolve the specific model class for this service instance.
     * Returns null if no model is mapped yet.
     */
    public function resolveServiceModel(): ?string
    {
        return static::serviceModelMap()[$this->slug] ?? null;
    }

    public function printServices()
    {
        return $this->hasMany(PrintService::class);
    }
}