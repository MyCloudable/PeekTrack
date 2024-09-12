<?php

namespace App\Models;

use Carbon\Carbon;
use App\Traits\Recoverable;
use Illuminate\Database\Eloquent\Model;
use App\Traits\SoftDeletesWithDeletedBy;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Timesheet extends Model
{
    use HasFactory, SoftDeletes, SoftDeletesWithDeletedBy, Recoverable;

    protected $fillable = ['crew_id', 'crew_type_id', 'user_id', 'clockin_time', 'clockout_time',
    'job_id', 'time_type_id', 'created_by', 'modified_by', 
    'weekend_out', 'reviewer_approval', 'reviewer_approval_by', 'reviewer_approval_at', 'payroll_approval', 'crew_member_approval',
    'per_diem'
    ];


    // protected $dates = ['clockin_time'];

    // Define the getter for the clockin_time attribute
    protected function clockinTime(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => Carbon::parse($value)->setTimezone(config('app.timezone'))->format('Y-m-d H:i'),
        );
    }
    protected function clockoutTime(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $value
                ? Carbon::parse($value)->setTimezone(config('app.timezone'))->format('Y-m-d H:i')
                : null,
        );
    }
}
