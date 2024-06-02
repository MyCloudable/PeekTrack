<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TravelTime extends Model
{
    use HasFactory;

    protected $fillable = ['crew_id', 'job_id', 'type', 'depart', 'arrive', 'created_by', 'modified_by'];
}
