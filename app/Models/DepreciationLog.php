<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DepreciationLog extends Model
{
    protected $fillable = [
        'asset_id',
        'period_date',
        'depreciation_amount',
        'book_value',
    ];

    protected $casts = [
        'period_date' => 'date',
        'depreciation_amount' => 'decimal:2',
        'book_value' => 'decimal:2',
    ];

    public function asset()
    {
        return $this->belongsTo(Asset::class);
    }
}
