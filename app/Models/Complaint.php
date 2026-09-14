<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\UploadedFile;

class Complaint extends Model
{
    use HasFactory;

    protected $fillable = [
        'complainant_id',
        'company_id',
        'category_id',
        'complaint_type_id',
        'assigned_to_user_id',
        'ticket_number',
        'description',
        'status',
        'priority',
        'is_deleted',
        'complaint_date',
        'deadline',
        'closed_at',
    ];

    protected $casts = [
        'is_deleted' => 'boolean',
        'complaint_date' => 'datetime',
        'deadline' => 'datetime',
        'closed_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('active', fn (Builder $builder) => $builder->where('is_deleted', false));
    }

    /**
     * Relasi ke data pelapor (complainant).
     */
    public function complainant(): BelongsTo
    {
        return $this->belongsTo(Complainant::class, 'complainant_id');
    }

    /**
     * Relasi ke data perusahaan terlapor (company).
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    /**
     * Relasi ke kategori masalah aduan.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(ComplaintCategory::class, 'category_id');
    }

    /**
     * Relasi ke jenis pengaduan (complaint type).
     */
    public function complaintType(): BelongsTo
    {
        return $this->belongsTo(ComplaintType::class, 'complaint_type_id');
    }

    /**
     * Relasi ke petugas/admin yang ditugaskan menangani aduan.
     */
    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to_user_id');
    }

    /**
     * Relasi ke berkas/file bukti pendukung.
     */
    public function attachments(): HasMany
    {
        return $this->hasMany(Attachment::class, 'complaint_id');
    }

    /**
     * Relasi ke riwayat perubahan status aduan.
     */
    public function updates(): HasMany
    {
        return $this->hasMany(Update::class, 'complaint_id');
    }

    /**
     * Relasi ke sesi survei kepuasan layanan.
     */
    public function surveys(): HasMany
    {
        return $this->hasMany(Survey::class);
    }

    /**
     * Relasi ke notifikasi terkait aduan ini.
     */
    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class);
    }

    public function addPhoto(UploadedFile $file): Attachment
    {
        return $this->attachments()->create([
            'file_name' => $file->getClientOriginalName(),
            'file_path' => $file->store('complaints/'.$this->id, 'public'),
            'file_type' => 'photo',
            'file_size' => $file->getSize(),
            'mime_type' => $file->getMimeType(),
        ]);
    }

    /**
     * Simpan berkas bukti pendukung (foto/pdf/video) beserta metadatanya.
     *
     * @param  array<string, mixed>  $meta
     */
    public function addAttachment(UploadedFile $file, array $meta = []): Attachment
    {
        return $this->attachments()->create([
            'file_name' => $file->getClientOriginalName(),
            'file_path' => $file->store('complaints/'.$this->id, 'public'),
            'file_type' => strtolower($file->getClientOriginalExtension()) ?: null,
            'file_size' => $file->getSize(),
            'mime_type' => $file->getMimeType(),
            'description' => $meta['description'] ?? null,
            'attachment_category_id' => $meta['attachment_category_id'] ?? null,
            'uploaded_by_user_id' => $meta['uploaded_by_user_id'] ?? null,
        ]);
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
