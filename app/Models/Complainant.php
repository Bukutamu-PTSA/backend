<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Complainant extends Model
{
    use HasFactory;

    protected $fillable = [
        'nama_lengkap',
        'nik',
        'alamat',
        'jenis_kelamin',
        'jabatan',
        'no_telp',
        'email',
    ];

    /**
     * Relasi ke seluruh berkas pengaduan yang diajukan oleh pelapor ini.
     */
    public function complaints(): HasMany
    {
        return $this->hasMany(Complaint::class);
    }
}
