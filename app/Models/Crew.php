<?php

namespace App\Models;

use App\Traits\Recoverable;
use Illuminate\Database\Eloquent\Model;
use App\Traits\SoftDeletesWithDeletedBy;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Crew extends Model
{
    use HasFactory, SoftDeletes, SoftDeletesWithDeletedBy, Recoverable;

    protected $fillable = ['crew_name', 'crew_type_id', 'superintendentId', 'crew_members', 
        'last_verified_date', 'created_by', 'modified_by', 'is_ready_for_verification'
    ];

    protected $casts = [
        'last_verified_at' => 'datetime:Y-m-d'
    ];

    public function superintendent()
    {
        return $this->belongsTo(User::class, 'superintendentId');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function modifiedBy()
    {
        return $this->belongsTo(User::class, 'modified_by');
    }

    public function crewType()
    {
        return $this->belongsTo(CrewType::class);
    }

    protected function crewMembers(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => json_decode($value, true),
            set: fn ($value) => json_encode($value),
        );
    } 
}
