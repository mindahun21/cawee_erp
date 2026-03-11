<?php

namespace App\Models\Procurement;

use Illuminate\Database\Eloquent\Model;

class ProcurementBidSecurity extends Model
{
    protected $fillable = ['name', 'is_active'];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }
}
