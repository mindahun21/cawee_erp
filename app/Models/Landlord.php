<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Landlord extends Model
{
    use SoftDeletes;

    protected $table = 'hr_landlords';

    protected $fillable = [
        'name',
        'phone',
        'email',
        'address',
        'notes',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function agreements(): HasMany
    {
        return $this->hasMany(OfficeRentAgreement::class);
    }
}

