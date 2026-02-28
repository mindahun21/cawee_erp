<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LayoffChecklistItem extends Model
{
    protected $table = 'hr_layoff_checklist_items';

    protected $fillable = [
        'title', 'responsible_party', 'description', 'sort_order', 'is_active',
    ];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }
}
