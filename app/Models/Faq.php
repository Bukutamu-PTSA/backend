<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Faq extends Model
{
    use HasFactory;

    protected $fillable = [
        'question',
        'answer',
        'category',
        'publication_status',
        'order_number',
        'approved_by_user_id',
        'view_count',
        'published_at',
    ];

    protected $casts = [
        'order_number' => 'integer',
        'view_count' => 'integer',
        'published_at' => 'datetime',
    ];

    /**
     * Relasi ke user yang menyetujui/mempublikasikan FAQ ini.
     */
    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by_user_id');
    }
}
