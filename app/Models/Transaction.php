<?php

namespace App\Models;

use App\Filters\TransactionFilters;
use Essa\APIToolKit\Filters\Filterable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class Transaction extends Model
{
	use HasFactory, Filterable;

	protected string $default_filters = TransactionFilters::class;

	/**
	 * Mass-assignable attributes.
	 *
	 * @var array
	 */
	protected $fillable = [
		'order_id',
		'user_id',
		'paymentId',
		'status',
		'amount',
	];
	protected $casts = [
		'created_at' => 'date:Y-m-d',
		'updated_at' => 'date:Y-m-d',
	];
	public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
	{
		return $this->belongsTo(\App\Models\User::class);
	}

	public function order()
	{
		return $this->belongsTo(Order::class);
	}

}