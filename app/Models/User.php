<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\UserTypeEnum;

use Illuminate\Database\Eloquent\Attributes\Guarded;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

#[Guarded(['id'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    
    use HasFactory,HasApiTokens, Notifiable;


    public function provider(): HasOne
    {
        return $this->hasOne(Provider::class);
    }

    /**
     * Get all contact messages sent by this user.
     */
    public function contactMessages(): HasMany
    {
        return $this->hasMany(ContactMessage::class);
    }

    /**
     * Get all comments written by this user.
     */
    public function comments(): HasMany
    {
        return $this->hasMany(ServiceComment::class);
    }

    /**
     * Get all properties owned by this user (if property_owner).
     */
    public function properties(): HasMany
    {
        return $this->hasMany(Property::class, 'user_id');
    }


    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'type' => UserTypeEnum::class,
        ];
    }
}
