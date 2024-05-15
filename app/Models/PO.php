<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PO extends Model
{
    use HasFactory;
	
	protected $table = 'job_po';
	
	protected $fillable = [
	    'job_number',
		'link',
        'phase',
        'userId',
        'po_number',
        'signer_name',
		'signature',
		'notes',

	];
}