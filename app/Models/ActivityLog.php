<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivityLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'action_type',
        'entity_type',
        'entity_id',
        'old_value',
        'new_value',
        'ip_address',
        'description',
    ];

    protected $casts = [
        'old_value' => 'array',
        'new_value' => 'array',
    ];

    /**
     * Relasi balik ke user yang melakukan aksi.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
