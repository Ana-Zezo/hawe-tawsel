<?php

namespace App\Models;

use App\Models\Driver;
use Illuminate\Database\Eloquent\Model;

class Withdraw extends Model
{
    protected $fillable = ['driver_id', 'amount', 'status','totalOrder','country_id'];
    
    
     public function driver()
    {
        return $this->belongsTo(Driver::class);
    }
      public function country()
    {
        return $this->belongsTo(Country::class, 'country_id');
    }
}