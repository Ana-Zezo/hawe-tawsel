<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Wallet extends Model
{
    use HasFactory;
    protected $fillable = ['user_id', 'amount', 'type', 'status'];
    // protected $casts = [

    // ];
    protected function casts(): array
    {
        return [
            'created_at' => 'date:Y-m-d',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}