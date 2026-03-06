<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CriteriaGroup extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = [];

    protected $hidden = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    public function form()
    {
        return $this->belongsTo(EvaluationForm::class);
    }

    public function criteria()
    {
        return $this->hasMany(EvaluationFormCriteria::class, 'criteria_group_id');
    }
}
