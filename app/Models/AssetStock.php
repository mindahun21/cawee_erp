<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AssetStock extends Model
{
    protected $fillable = [
        'asset_id',
        'location_id',
        'department_id',
        'quantity',
    ];

    public function asset()
    {
        return $this->belongsTo(Asset::class);
    }

    public function location()
    {
        return $this->belongsTo(Location::class);
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }
}
