<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InventoryMovementReason extends Model
{
    protected $fillable = ['name', 'description'];

    public function movements()
    {
        return $this->hasMany(InventoryMovement::class, 'reason_id');
    }
}
