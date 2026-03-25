<?php

declare(strict_types=1);

namespace App\Models\ME;

use App\Models\ME\Concerns\LogsMeAudit;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MeLocation extends Model
{
    use HasFactory;
    use LogsMeAudit;

    protected $table = 'me_locations';

    protected $fillable = [
        'name',
        'type',
        'parent_id',
        'lat',
        'lng',
    ];

    protected $casts = [
        'lat'       => 'decimal:7',
        'lng'       => 'decimal:7',
        'parent_id' => 'integer',
    ];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(MeLocation::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(MeLocation::class, 'parent_id');
    }

    public function feedbacks(): HasMany
    {
        return $this->hasMany(MeBeneficiaryFeedback::class, 'location_id');
    }
}
