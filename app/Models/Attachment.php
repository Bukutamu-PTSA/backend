<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Attachment extends Model
{
    use HasFactory;

    protected $fillable = [
        'complaint_id',
        'attachment_category_id',
        'file_name',
        'file_path',
        'file_type',
        'file_size',
        'mime_type',
        'description',
        'uploaded_by_user_id',
        'is_verified',
    ];

    protected $casts = [
        'file_size' => 'integer',
        'is_verified' => 'boolean',
    ];

    /**
     * Relasi balik ke tiket pengaduan yang memiliki lampiran ini.
     */
    public function complaint(): BelongsTo
    {
        return $this->belongsTo(Complaint::class, 'complaint_id');
    }

    /**
     * Relasi ke kategori berkas lampiran.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(AttachmentCategory::class, 'attachment_category_id');
    }

    /**
     * Relasi ke user yang mengunggah berkas ini.
     */
    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by_user_id');
    }
}
