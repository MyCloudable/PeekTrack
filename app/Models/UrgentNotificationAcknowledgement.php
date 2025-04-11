<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UrgentNotificationAcknowledgement extends Model
{
    use HasFactory;
	protected $table = 'urg_notice_ack';

protected $fillable = [
    'user_id',
    'notification_id',
    'acknowledged_at'
];

	public function notification()
{
    return $this->belongsTo(UrgentNotification::class, 'notification_id');
}


public function user()
{
    return $this->belongsTo(User::class);
}

}
