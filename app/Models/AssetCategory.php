<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AssetCategory extends Model
{
    protected $fillable = ['name', 'description', 'useful_life'];

    public function assets()
    {
        return $this->hasMany(Asset::class);
    }
}
