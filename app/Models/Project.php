<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Project extends Model
{
    protected $fillable = ['name', 'description'];

    public function purchaseRequests()
    {
        return $this->hasMany(PurchaseRequest::class);
    }
}
