<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Survey extends Model
{
    use HasFactory;

    protected $fillable = [
        'complaint_id',
        'created_by_admin_id',
        'status',
        'closed_at',
    ];

    protected $casts = [
        'closed_at' => 'datetime',
    ];

    /**
     * Relasi balik ke tiket pengaduan yang disurvei.
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
