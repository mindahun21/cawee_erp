<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ContractType extends Model
{
    protected $table = 'hr_contract_types';
    protected $fillable = ['name', 'is_active'];
    protected $casts = ['is_active' => 'boolean'];
}
