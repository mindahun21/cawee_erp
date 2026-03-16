<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InventoryMovementStatus extends Model
{
    protected $fillable = ['name', 'description'];

    public function movements()
    {
        return $this->hasMany(InventoryMovement::class, 'status_id');
    }
}
