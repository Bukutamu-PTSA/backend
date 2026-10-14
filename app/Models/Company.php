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
        'sector_id',
        'size_id',
        'alamat',
        'jumlah_naker',
        'provinsi',
        'kota_kab',
        'kecamatan',
        'kelurahan',
        'no_telp',
        'email',
    ];

    /**
     * Relasi ke daftar seluruh pengaduan yang melibatkan perusahaan ini.
     */
    public function sector(): BelongsTo
    {
        return $this->belongsTo(IndustrialSector::class, 'sector_id');
    }

    public function size(): BelongsTo
    {
        return $this->belongsTo(CompanySize::class, 'size_id');
    }

    public function complaints(): HasMany
    {
        return $this->hasMany(Complaint::class);
    }
}
