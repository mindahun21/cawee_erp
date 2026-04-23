<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class SharedFolder extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'parent_id',
        'name',
        'description',
        'visibility',
        'owner_id',
        'context_type',
        'context_id',
    ];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function files(): HasMany
    {
        return $this->hasMany(SharedFile::class, 'folder_id');
    }

    public function shares(): HasMany
    {
        return $this->hasMany(FileShare::class, 'shared_folder_id');
    }

    public function context(): MorphTo
    {
        return $this->morphTo();
    }

    public function breadcrumbTrail(): array
    {
        $trail = [];
        $current = $this;

        while ($current) {
            array_unshift($trail, [
                'id' => $current->id,
                'name' => $current->name,
            ]);

            $current = $current->parent;
        }

        return $trail;
    }

    public function descendantsAndSelfIds(): array
    {
        $ids = [$this->getKey()];

        foreach ($this->children as $child) {
            $ids = array_merge($ids, $child->descendantsAndSelfIds());
        }

        return $ids;
    }

    public function pathLabel(): string
    {
        return implode(' / ', array_column($this->breadcrumbTrail(), 'name'));
    }
}
