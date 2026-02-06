<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable=['name', 'email', 'password', 'role_id','password_confirmation', 'phone', 'location', 'old_password','picture', 'manager_id'];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];
    
    public function setPasswordAttribute($password)
    {
        $this->attributes['password'] = bcrypt($password);
    }

   /**
     * Check if the user has admin role
     */
    public function isAdmin()
    {
        return $this->role_id == 1;
    }

    /**
     * Check if the user has creator role
     */
    public function isCreator()
    {
        return $this->role_id == 2;
    }

    /**
     * Check if the user has user role
     */
    public function isMember()
    {
        return $this->role_id == 3;
    }

    public function role(){

        return $this->belongsTo(Role::class);
    }
	
	public function acknowledgedUrgentNotifications()
    {
        return $this->belongsToMany(UrgentNotification::class, 'urg_notice_ack', 'user_id', 'notification_id')
                    ->withPivot('acknowledged_at')
                    ->withTimestamps();
    }


    // Scope to get only active employees
    public function scopeActiveEmployees($query)
    {
        return $query->where('active', 1)
            ->where(function ($q) {

                $q->whereNull('termDate')
                ->orWhereIn('termDate', ['', 'EMPTY'])

                ->orWhere(function ($q2) {
                    $q2->whereNotNull('termDate')
                        ->whereNotIn('termDate', ['', 'EMPTY'])
                        ->whereNotNull('rehireDate')
                        ->whereNotIn('rehireDate', ['', 'EMPTY'])
                        ->whereRaw("
                            STR_TO_DATE(rehireDate, '%c/%e/%Y')
                            >=
                            STR_TO_DATE(termDate, '%c/%e/%Y')
                        ");
                });

            });
    }


}
