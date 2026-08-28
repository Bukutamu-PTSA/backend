<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Notification extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'complaint_id',
        'title',
        'content',
        'is_read',
    ];

    protected $casts = [
        'is_read' => 'boolean',
    ];

    /**
     * Relasi balik ke user penerima notifikasi.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relasi balik ke tiket pengaduan terkait notifikasi.
     */
    public function complaint(): BelongsTo
    {
        return $this->belongsTo(Complaint::class);
    }
}
