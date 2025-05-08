<?php

namespace App\Models;

use App\Models\User;
use App\Models\Country;
use App\Models\NotificationAdmin;
use Illuminate\Database\Eloquent\Model;

class Complaint extends Model
{
    protected $fillable = ['user_id', 'orderNumber', 'message', 'reply','country_id'];
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
     public function country()
    {
        return $this->belongsTo(Country::class, 'country_id');
    }
    public function notification()
    {
        return $this->hasOne(NotificationAdmin::class);
    }
}