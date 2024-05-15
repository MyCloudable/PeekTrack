<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class File extends Model
{
    use HasFactory;
    protected $fillable = [
		'job_number',
        'name',
		'description',
		'type',
		'doctype',
        'file_path',
		'link',
		'active'
    ];
}