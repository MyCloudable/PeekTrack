<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UrgentNotificationUserAcknowledgement extends Model
{
    use HasFactory;
	 protected $table = 'urg_notice_ack';

    protected $fillable = [
        'notification_id',
        'user_id',
        'acknowledged_at'
    ];

    public $timestamps = true;
}
