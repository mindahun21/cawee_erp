<?php

namespace App\Models\ME;

use App\Models\ME\Concerns\LogsMeAudit;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MeSurveyQuestion extends Model
{
    use HasFactory;
    use LogsMeAudit;

    protected $table = 'me_survey_questions';

    public $timestamps = false;

    protected $fillable = [
        'survey_id',
        'question_text',
        'question_type',
        'options',
        'is_required',
    ];

    protected $casts = [
        'survey_id' => 'integer',
        'options' => 'array',
        'is_required' => 'boolean',
    ];

    public function survey(): BelongsTo
    {
        return $this->belongsTo(MeSurvey::class, 'survey_id');
    }

    public function answers(): HasMany
    {
        return $this->hasMany(MeSurveyAnswer::class, 'question_id');
    }
}
