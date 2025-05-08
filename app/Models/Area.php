<?php

namespace App\Models;

use App\Filters\AreaFilters;
use Essa\APIToolKit\Filters\Filterable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class Area extends Model
{
    use HasFactory, Filterable;

    protected string $default_filters = AreaFilters::class;

    /**
     * Mass-assignable attributes.
     *
     * @var array
     */
    protected $fillable = [
        'name_ar',
        'name_en',
        'country_id',
        'coordinates',
        'status',
        'latitude',
        'longitude',
        'radius',
    ];
    protected $casts = [
        'coordinates' => 'array',
    ];

    public function senderOrders()
    {
        return $this->hasMany(Order::class, 'area_sender_id');
    }

    public function receiverOrders()
    {
        return $this->hasMany(Order::class, 'area_receiver_id');
    }
    
     public function orders()
    {
        return $this->hasMany(Order::class); // العلاقة بين الـ Area والـ Orders
    }
    public function country()
{
    return $this->belongsTo(Country::class);
}
}
