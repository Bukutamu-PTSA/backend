<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SurveyQuestion extends Model
{
    use HasFactory;

    protected $fillable = [
        'survey_id',
        'question_number',
        'question_text',
        'type',
        'options',
    ];

    protected $casts = [
        'options' => 'array',
    ];

    /**
     * Relasi balik ke sesi survei induk.
     */
    public function survey(): BelongsTo
    {
        return $this->belongsTo(Survey::class);
    }

    /**
     * Relasi ke seluruh jawaban yang diberikan untuk pertanyaan ini.
     */
    public function responses(): HasMany
    {
        return $this->hasMany(SurveyResponse::class);
    }
}
