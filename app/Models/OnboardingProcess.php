<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class OnboardingProcess extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = [];

    protected $hidden = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];
    public function sendTo()
    {
        return $this->belongsTo(User::class, 'send_to_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'added_by');
    }

    protected static function booted()
    {
        static::creating(function ($criteria) {
            $criteria->added_by = auth()->id();
        });
    }
}
