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

    /**
     * Relasi ke semua file attachment berkas pendukung dalam kategori ini.
     */
    public function attachments(): HasMany
    {
        return $this->hasMany(Attachment::class);
    }
}
