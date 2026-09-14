<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Survey extends Model
{
    use HasFactory;

    protected $fillable = [
        'complaint_id',
        'created_by_admin_id',
        'survey_date',
        'nama_petugas',
        'komunikasi_petugas',
        'penjelasan_materi',
        'sarana_prasarana',
        'catatan',
        'rating_overall',
        'is_anonymous',
        'respondent_email',
        'respondent_phone',
        'status',
        'closed_at',
    ];

    protected $casts = [
        'survey_date' => 'date',
        'rating_overall' => 'integer',
        'is_anonymous' => 'boolean',
        'closed_at' => 'datetime',
    ];

    /**
     * Relasi balik ke tiket pengaduan yang disurvei (opsional).
     */
    public function complaint(): BelongsTo
    {
        return $this->belongsTo(Complaint::class);
    }

    /**
     * Relasi ke profil admin/petugas yang menangani atau menginisiasi survei.
     */
    public function admin(): BelongsTo
    {
        return $this->belongsTo(AdminProfile::class, 'created_by_admin_id');
    }

    /**
     * Relasi ke seluruh respon/jawaban yang masuk untuk sesi survei ini.
     */
    public function responses(): HasMany
    {
        return $this->hasMany(SurveyResponse::class);
    }

    /**
     * Relasi ke butir pertanyaan yang dijawab dalam survei ini (via responses).
     */
    public function questions(): BelongsToMany
    {
        return $this->belongsToMany(SurveyQuestion::class, 'survey_responses')
            ->withPivot('answer')
            ->withTimestamps();
    }
}
