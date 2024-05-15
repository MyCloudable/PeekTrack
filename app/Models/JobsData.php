<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JobsData extends Model
{
    use HasFactory;
	
	protected $table = 'job_data';
	
	protected $fillable = [
	    'job_number',
        'phase',
        'description',
        'est_qty',
        'unit_of_measure',

	];
}
