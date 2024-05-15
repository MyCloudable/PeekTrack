<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Jobreview extends Model
{
    use HasFactory;
	
		protected $table = 'jobreview';
	
	protected $fillable = [
	    'link',
        'job_number',
        'reviewed_by',
        'date_reviewed',


	];
}
