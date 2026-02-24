<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class AppraisalTemplate extends Model
{
    use SoftDeletes;

    protected $fillable = ['name', 'type', 'is_active', 'description'];

    public function sections(): HasMany
    {
        return $this->hasMany(AppraisalSection::class, 'template_id')->orderBy('sort_order');
    }

    public function appraisals(): HasMany
    {
        return $this->hasMany(Appraisal::class, 'template_id');
    }
}
