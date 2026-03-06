<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Location extends Model
{
    protected $fillable = ['name', 'type', 'description', 'parent_id', 'project_id'];

    public function parent()
    {
        return $this->belongsTo(Location::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(Location::class, 'parent_id');
    }

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function assets()
    {
        return $this->hasMany(Asset::class);
    }

    public function movementsFrom()
    {
        return $this->hasMany(InventoryMovement::class, 'from_location_id');
    }

    public function movementsTo()
    {
        return $this->hasMany(InventoryMovement::class, 'to_location_id');
    }

    public function stocks()
    {
        return $this->hasMany(AssetStock::class);
    }
}
