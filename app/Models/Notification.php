<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;


class Notification extends Model
{
    protected $fillable = [
        'notifiable_id',
        'notifiable_type',
        'title_ar',
        'title_en',
        'description_ar',
        'description_en',
        'is_read'

    ];


    public function notifiable(): MorphTo
    {
        return $this->morphTo();
    }





    // public function user()
    // {
    //     return $this->belongsTo(User::class);
    // }


}