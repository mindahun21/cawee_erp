<?php

namespace App\Models\ME;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MeSurveyAnswer extends Model
{
    use HasFactory;

    protected $table = 'me_survey_answers';

    public $timestamps = false;

    protected $fillable = [
        'response_id',
        'question_id',
        'answer_text',
        'answer_number',
        'answer_json',
    ];

    protected $casts = [
        'response_id' => 'integer',
        'question_id' => 'integer',
        'answer_number' => 'decimal:2',
        'answer_json' => 'array',
    ];

    public function response(): BelongsTo
    {
        return $this->belongsTo(MeSurveyResponse::class, 'response_id');
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(MeSurveyQuestion::class, 'question_id');
    }
}
