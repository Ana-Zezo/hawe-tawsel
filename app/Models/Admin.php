<?php

namespace App\Models;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

use Illuminate\Database\Eloquent\Model;

class Admin extends Authenticatable
{
    use HasFactory, Notifiable, HasApiTokens;
    protected function casts(): array
    {
        return [
            'password' => 'hashed',
        ];
    }
    protected $fillable = [
       'name',
        'phone',
        'password',
        'country',
        'city',
        'image',
        'otp'
    ];
}