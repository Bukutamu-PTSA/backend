<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Complaint extends Model
{
    use HasFactory;

    protected $fillable = [
        'complainant_id',
        'company_id',
        'category_id',
        'assigned_to',
        'ticket_number',
        'title',
        'description',
        'status',
        'priority',
        'follow_up_date',
        'internal_notes',
        'complaint_date',
    ];

    protected $casts = [
        'complaint_date' => 'datetime',
        'follow_up_date' => 'datetime',
    ];

    /**
     * Relasi ke data pelapor (complainant).
     */
    public function complainant(): BelongsTo
    {
        return $this->belongsTo(Complainant::class);
    }

    /**
     * Relasi ke data perusahaan terlapor (company).
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Relasi ke kategori masalah aduan.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(ComplaintCategory::class, 'category_id');
    }

    /**
     * Relasi ke profil admin/petugas yang menangani tiket.
     */
    public function assignedAdmin(): BelongsTo
    {
        return $this->belongsTo(AdminProfile::class, 'assigned_to');
    }

    /**
     * Relasi ke berkas/file bukti pendukung.
     */
    public function attachments(): HasMany
    {
        return $this->hasMany(Attachment::class);
    }

    /**
     * Relasi ke sesi survei kepuasan layanan.
     */
    public function surveys(): HasMany
    {
        return $this->hasMany(Survey::class);
    }

    /**
     * Relasi ke riwayat log/perubahan status aduan.
     */
    public function updates(): HasMany
    {
        return $this->hasMany(Update::class);
    }

    /**
     * Relasi ke notifikasi terkait aduan ini.
     */
    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class);
    }

    /**
     * Local Scope: Filter berdasarkan status aduan.
     */
    public function scopeByStatus(Builder $query, string $status): Builder
    {
        return $query->where('status', $status);
    }

    /**
     * Local Scope: Filter berdasarkan ID kategori aduan.
     */
    public function scopeByCategory(Builder $query, int $categoryId): Builder
    {
        return $query->where('category_id', $categoryId);
    }

    /**
     * Local Scope: Filter berdasarkan rentang tanggal aduan.
     */
    public function scopeByDateRange(Builder $query, string $startDate, string $endDate): Builder
    {
        return $query->whereBetween('complaint_date', [$startDate, $endDate]);
    }
}
