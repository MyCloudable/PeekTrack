<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Timesheet extends Model
{
    use HasFactory;

    protected $fillable = ['crew_id', 'crew_type_id', 'user_id', 'clockin_time', 'clockout_time',
    'job_id', 'time_type_id', 'created_by', 'modified_by', 
    'weekend_out', 'reviewer_approval', 'reviewer_approval_by', 'reviewer_approval_at', 'payroll_approval', 'crew_member_approval'
    ];

    // protected $casts = [
    //     'clockin_time' => 'datetime:Y-m-d',
    // ];

    // public function getClockinTimeAttribute($date){
    //     return $date->format('H:i');
    // }

    // protected function clockinTime(): Attribute
    // {
    //     return Attribute::make(
    //         // get: fn ($value) => date('H:i', strtotime($value)),
    //         get: fn ($value) => Carbon::createFromFormat('H:i:s',$value)->format('H:i'),
    //     );
    // } 

    // protected function clockoutTime(): Attribute
    // {
    //     return Attribute::make(
    //         // get: fn ($value) => date('H:i', strtotime($value)),
    //         get: fn ($value) => 'test',
    //     );
    // } 
}
