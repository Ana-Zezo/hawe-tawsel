<?php

namespace App\Models;

use App\Models\User;
use App\Models\Driver;
use App\Filters\OrderFilters;
use App\Policies\OrderPolicy;
use Essa\APIToolKit\Filters\Filterable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;


class Order extends Model
{
	use HasFactory, Filterable;

	protected string $default_filters = OrderFilters::class;

	/**
	 * Mass-assignable attributes.
	 *
	 * @var array
	 */
	protected $fillable = [
		'user_id',
		'driver_id',
		'area_sender_id',
		'area_receiver_id',
		'orderNumber',
		'receiver_id',
		'status_break',
		'order_type',
		'cover',
		'image',
		'description',
		'status',
		'coverPrice',
		'basePrice',
		'totalPrice',
		'weight',
		'secret_key',
		'pickup_date',
		'delivery_date',
		'pickup_time',
		'delivery_time',
		'save_sender',
		'save_receiver',
		"name_receiver",
		"phone_receiver",
		"country_receiver",
		"city_receiver",
		"area_street_receiver",
		"neighborhood_receiver",
		"build_number_receiver",
		"latitude_receiver",
		"longitude_receiver",
		'name_sender',
		'phone_sender',
		'country_sender',
		'city_sender',
		'area_street_sender',
		'neighborhood_sender',
		'build_number_sender',
		'latitude_sender',
		'longitude_sender',
		'product_name',
		'area_id',
		'rate'
	];
	public function user()
	{
		return $this->belongsTo(User::class);
	}

	public function areaSender()
	{
		return $this->belongsTo(Area::class, 'area_sender_id');
	}

	public function receiverArea()
	{
		return $this->belongsTo(Area::class, 'area_receiver_id');
	}

	public function driver(): BelongsTo
	{
		return $this->belongsTo(Driver::class);
	}

	public function transactions()
	{
		return $this->hasMany(Transaction::class, 'order_id');
	}

	protected static function boot()
	{
		parent::boot();

		static::creating(function ($order) {
			$order->orderNumber = self::generateUniqueUUID();
		});
	}

	private static function generateUniqueUUID()
	{
		do {
			$orderNumber = self::generateCustomUUID();
		} while (self::uuidExists($orderNumber));

		return $orderNumber;
	}

	private static function generateCustomUUID()
	{
		$characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
		return substr(str_shuffle(str_repeat($characters, 6)), 0, 9);
	}

	private static function uuidExists($orderNumber)
	{
		return self::where('orderNumber', $orderNumber)->exists();
	}

}