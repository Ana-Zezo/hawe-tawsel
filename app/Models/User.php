<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Models\Order;
use App\Models\Sender;
use App\Models\Complaint;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, HasApiTokens, Notifiable;


    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'first_name',
        'country_id',
        'last_name',
        'password',
        'otp',
        'phone',
        'is_verify',
        'image',
        'wallet',
        'fcm_token',
    ];
    public function routeNotificationForFcm()
    {
        return $this->fcm_token;
    }
    // public function routeNotificationForFcm()
    // {
    //     return $this->getDeviceTokens();
    // }

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
    public function complains()
    {
        return $this->hasMany(Complaint::class);
    }

    public function wallet()
    {
        return $this->hasMany(Wallet::class);
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