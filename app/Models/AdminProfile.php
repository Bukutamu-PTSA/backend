<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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

    /**
     * Relasi ke daftar pengaduan yang ditugaskan kepada petugas admin ini.
     */
    public function assignedComplaints(): HasMany
    {
        return $this->hasMany(Complaint::class, 'assigned_to');
    }
}
