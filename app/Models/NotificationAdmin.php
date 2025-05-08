<?php

namespace App\Models;

use App\Models\Withdraw;
use App\Models\Complaint;
use Illuminate\Database\Eloquent\Model;

class NotificationAdmin extends Model
{
    protected $fillable = ['title', 'description', 'is_read', 'withdraw_id', 'complaint_id'];

    public function withdraw()
    {
        return $this->belongsTo(Withdraw::class);
    }

    public function complaint()
    {
        return $this->belongsTo(Complaint::class);
    }
}