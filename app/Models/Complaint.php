<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

class Complaint extends Model
{
    use HasFactory;

    protected $fillable = [
        'complainant_id',
        'company_id',
        'category_id',
        'ticket_number',
        'description',
        'status',
        'priority',
        'is_deleted',
        'complaint_date',
    ];

    protected $casts = [
        'is_deleted' => 'boolean',
        'complaint_date' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('active', fn(Builder $builder) => $builder->where('is_deleted', false));
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
     * Relasi ke berkas/file bukti pendukung.
     */
    public function attachments(): HasMany
    {
        return $this->hasMany(Attachment::class, 'complaint_id');
    }

    /**
     * Relasi ke notifikasi terkait aduan ini.
     */
    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class);
    }


    public function addAttachment(UploadedFile $file): Attachment
    {
        $mimeType = $file->getMimeType();

        $filename = Str::uuid() . '.' . $file->extension();

        $path = $file->storeAs(
            'complaints/' . $this->id,
            $filename,
            'local'
        );

        try {
            return $this->attachments()->create([
                'file_name' => $file->getClientOriginalName(),
                'file_path' => $path,
                'file_type' => $mimeType === 'application/pdf'
                    ? 'document'
                    : 'photo',
                'file_size' => $file->getSize(),
                'mime_type' => $mimeType,
            ]);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Storage::disk('private')->delete($path);

            throw $e;
        }
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
