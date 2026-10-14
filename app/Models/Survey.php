<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Survey extends Model
{
    use HasFactory;

    protected $fillable = [
        'status',
        'closed_at',
    ];

    protected $casts = [
        'closed_at' => 'datetime',
    ];

    /**
     * Pertanyaan baku untuk form survei publik tunggal.
     *
     * @var array<int, array{question_number: int, question_text: string, type: string, options: array<int, string>|null}>
     */
    public const DEFAULT_QUESTIONS = [
        [
            'question_number' => 1,
            'question_text' => 'Nama Petugas',
            'type' => 'text',
            'options' => null,
        ],
        [
            'question_number' => 2,
            'question_text' => 'Bagaimana Komunikasi Petugas Dalam Memberikan Layanan',
            'type' => 'likert',
            'options' => ['Baik', 'Cukup', 'Kurang'],
        ],
        [
            'question_number' => 3,
            'question_text' => 'Bagaimana Penjelasan Materi yang Diberikan oleh Petugas',
            'type' => 'likert',
            'options' => ['Baik', 'Cukup', 'Kurang'],
        ],
        [
            'question_number' => 4,
            'question_text' => 'Bagaimana Sarana dan Prasarana Layanan PTSA',
            'type' => 'likert',
            'options' => ['Baik', 'Cukup', 'Kurang'],
        ],
        [
            'question_number' => 5,
            'question_text' => 'Catatan',
            'type' => 'text',
            'options' => null,
        ],
    ];

    /**
     * Relasi ke daftar butir pertanyaan survei.
     */
    public function questions(): HasMany
    {
        return $this->hasMany(SurveyQuestion::class);
    }

    /**
     * Relasi ke seluruh respon/jawaban yang masuk untuk sesi survei ini.
     */
    public function responses(): HasMany
    {
        return $this->hasMany(SurveyResponse::class);
    }
}
