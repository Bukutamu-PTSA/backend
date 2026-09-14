<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdminProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'nama_lengkap',
        'nama_petugas',
        'unit_kerja',
        'golongan',
    ];

    /**
     * Relasi balik ke akun User.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
