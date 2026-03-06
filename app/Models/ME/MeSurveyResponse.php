<?php

namespace App\Models\ME;

use App\Models\ME\Concerns\LogsMeAudit;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MeSurveyResponse extends Model
{
    use HasFactory;
    use LogsMeAudit;

    protected $table = 'me_survey_responses';

    public $timestamps = false;

    protected $fillable = [
        'survey_id',
        'submitted_at',
        'respondent_code',
        'location',
    ];

    protected $casts = [
        'survey_id' => 'integer',
        'submitted_at' => 'datetime',
    ];

    public function survey(): BelongsTo
    {
        return $this->belongsTo(MeSurvey::class, 'survey_id');
    }

    public function answers(): HasMany
    {
        return $this->hasMany(MeSurveyAnswer::class, 'response_id');
    }
}
