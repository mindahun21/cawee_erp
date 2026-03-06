<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Location extends Model
{
    protected $table = 'hr_locations';

    protected $fillable = [
        'location_name',
        'address',
        'type',
    ];

    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class, 'location_id');
    }

    public function projects(): HasMany
    {
        return $this->hasMany(Project::class, 'location_id');
    }
}
