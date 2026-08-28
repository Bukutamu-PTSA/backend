<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Company extends Model
{
    use HasFactory;

    protected $fillable = [
        'nama_perusahaan',
        'sektor_industri',
        'alamat',
        'jumlah_naker',
        'size_id',
        'provinsi',
        'kota_kab',
        'kecamatan',
        'kelurahan',
        'no_telp',
        'email',
    ];

    /**
     * Relasi ke klasifikasi skala ukuran perusahaan.
     */
    public function size(): BelongsTo
    {
        return $this->belongsTo(CompanySize::class, 'size_id');
    }

    /**
     * Relasi ke daftar seluruh pengaduan yang melibatkan perusahaan ini.
     */
    public function complaints(): HasMany
    {
        return $this->hasMany(Complaint::class);
    }
}
