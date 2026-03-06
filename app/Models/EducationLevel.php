<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EducationLevel extends Model
{
    protected $table = 'hr_education_levels';
    protected $fillable = ['name', 'sort_order'];
}
