<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Production extends Model
{
    use HasFactory;
	
	protected $table = 'production';
	
	protected $fillable = [
	    'job_number',
		'userId',
        'phase',
        'description',
        'qty',
        'unit_of_measure',
		'mark_mill',
		'road_name',
		'phase_item_complete',
		'surface_type',
	];
}
