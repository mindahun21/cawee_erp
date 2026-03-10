<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Depreciation extends Model
{
    protected $fillable = [
        'name',
        'months',
    ];

    public function assets()
    {
        return $this->hasMany(Asset::class);
    }
}
