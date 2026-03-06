<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class EvaluationCriteria extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = [];

    protected $hidden = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    public function scores()
    {
        return $this->hasMany(Score::class);
    }

    public function group()
    {
        return $this->belongsTo(CriteriaGroup::class, 'criteria_group_id');
    }

    public function user()
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
