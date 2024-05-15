<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Jobentry extends Model
{
    use HasFactory;
	
	protected $table = 'jobentries';
	
	protected $fillable = [
	    'link',
		'job_number',
		'workdate',
		'submitted',
		'submitted_on',
		'userId',
		'name',
		'approved',
		'approvedBy',
		'approved_date',

	];

}
