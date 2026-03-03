<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TrainingType extends Model
{
    protected $table = 'hr_training_types';
    protected $fillable = ['name'];
}
