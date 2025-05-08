<?php

namespace App\Models;


use App\Models\User;
use App\Models\Order;
use App\Models\Driver;
use App\Models\Withdraw;
use App\Models\Complaint;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;


class Country extends Model
{
    use HasFactory;

    /**
     * Mass-assignable attributes.
     *
     * @var array
     */
    protected $fillable = [
        'name_en',
        'name_ar',
        'image',
        'country_code',
        'currency',
        'kilo',
        'cover_price',
        'tax_amount'
    ];

    public function users()
    {
        return $this->hasMany(User::class);
    }
    public function drivers()
    {
        return $this->hasMany(Driver::class);
    }
    public function orders()
    {
        return $this->hasMany(Order::class);
    }
    
    public function withdraws()
{
    return $this->hasMany(Withdraw::class);
}

public function complaints()
{
    return $this->hasMany(Complaint::class);
}

}