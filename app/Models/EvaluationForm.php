<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class EvaluationForm extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = [];

    protected $hidden = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    public function groups()
    {
        return $this->hasMany(CriteriaGroup::class);
    }
    public function position()
    {
        return $this->belongsTo(RecruitmentPosition::class);
    }
    public function addedBy()
    {
        return $this->belongsTo(User::class, 'added_by');
    }
    protected static function booted()
    {
        static::creating(function ($criteria) {
            $criteria->added_by = auth()->id();
        });
    }
}
