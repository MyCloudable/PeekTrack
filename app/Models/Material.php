<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Material extends Model
{
    use HasFactory;
	
		protected $table = 'material';
	
	protected $fillable = [
	    'link',
		'job_number',
		'userId',
        'phase',
        'description',
        'qty',
        'unit_of_measure',
		'supplier',
		'batch',
	];


}
