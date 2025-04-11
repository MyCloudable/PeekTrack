<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UrgentNotification extends Model
{
    use HasFactory;
protected $table = 'urg_notice';

protected $fillable = [
    'title',
    'message',
    'is_active',
    'created_by'
];


	public function acknowledgements()
{
    return $this->hasMany(UrgentNotificationUserAcknowledgement::class, 'notification_id');
}

public function acknowledgedByUsers()
{
    return $this->belongsToMany(User::class, 'urg_notice_ack', 'notification_id', 'user_id')
                ->withPivot('acknowledged_at')
                ->withTimestamps();
}




public function creator()
{
    return $this->belongsTo(User::class, 'created_by');
}

}
