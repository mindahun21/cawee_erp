<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FieldOfStudy extends Model
{
    protected $table = 'hr_fields_of_study';
    protected $fillable = ['name'];
}
