<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AttachmentCategory extends Model
{
    use HasFactory;

    protected $fillable = [
        'category_name',
        'description',
        'required_count',
    ];

    protected $casts = [
        'required_count' => 'integer',
    ];

    /**
     * Relasi ke seluruh berkas lampiran dalam kategori ini.
     */
    public function attachments(): HasMany
    {
        return $this->hasMany(Attachment::class, 'attachment_category_id');
    }
}
