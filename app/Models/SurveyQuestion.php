<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SurveyQuestion extends Model
{
    use HasFactory;

    protected $fillable = [
        'question_text',
        'answer_type',
        'option_a',
        'option_b',
        'option_c',
        'is_required',
        'order_number',
    ];

    protected $casts = [
        'is_required' => 'boolean',
        'order_number' => 'integer',
    ];

    /**
     * Relasi ke seluruh jawaban yang diberikan untuk pertanyaan ini.
     */
    public function responses(): HasMany
    {
        return $this->hasMany(SurveyResponse::class);
    }

    /**
     * Relasi ke sesi survei yang menjawab pertanyaan ini (via responses).
     */
    public function surveys(): BelongsToMany
    {
        return $this->belongsToMany(Survey::class, 'survey_responses')
            ->withPivot('answer')
            ->withTimestamps();
    }
}
