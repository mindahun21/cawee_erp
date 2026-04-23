<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Department extends Model
{
    protected $table = 'hr_departments';

    protected $fillable = ['name', 'code', 'description'];

    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class);
    }

    public function jobPositions(): HasMany
    {
        return $this->hasMany(JobPosition::class);
    }

    public function plans(): HasMany
    {
        return $this->hasMany(Plan::class);
    }
}
