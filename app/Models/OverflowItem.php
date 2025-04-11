<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OverflowItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'job_id', 'crew_type_id', 'branch_id', 'notes', 'traffic_shift', 'timein_date', 'timeout_date',
        'superintendent_id', 'completion_date', 'complete_user_id',
        'created_by','approved'
    ];

    public function job()
    {
        return $this->belongsTo(Job::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class, 'branch_id', 'department'); // Maps branch_id to department
    }

    public function superintendent()
    {
        return $this->belongsTo(User::class, 'superintendent_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
