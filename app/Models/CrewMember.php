<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CrewMember extends Model
{
    use HasFactory;

    protected $fillable = ['crew_id', 'userid', 'created_by', 'modified_by'];

    public function crewMemberUser()
    {
        return $this->belongsTo(User::class, 'userid');
    }
}
