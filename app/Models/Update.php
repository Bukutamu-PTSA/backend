<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Update extends Model
{
    use HasFactory;

    protected $fillable = [
        'complaint_id',
        'old_status',
        'new_status',
        'notes',
    ];

    /**
     * Relasi balik ke tiket pengaduan yang diperbarui statusnya.
     */
    public function complaint(): BelongsTo
    {
        return $this->belongsTo(Complaint::class);
    }
}
