<?php

namespace App\Models;

use App\Models\Country;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

class Driver extends Authenticatable
{
    use HasFactory, Notifiable, HasApiTokens;
    protected function casts(): array
    {
        return [
            'password' => 'hashed',
        ];
    }
    protected $fillable = [
        'first_name',
        'country_id',
        'last_name',
        'password',
        'phone',
        'otp',
        'status',
       'wallet',
        'totalOrder',
        'is_approve',
        'is_verify',
        'longitude',
        'latitude',
        'city',
        'image',
        'neighborhood',
        'card_image',
        'license_image',
        'license_self_image',
        'fcm_token',
    ];
    public function routeNotificationForFcm()
    {
        return $this->fcm_token;
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }
    public function country()
    {
        return $this->belongsTo(Country::class);
    }

    public function notifications()
    {
        return $this->morphMany(Notification::class, 'notifiable');
    }

}