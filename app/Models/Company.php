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
        'provinsi',
        'kota_kab',
        'kecamatan',
        'kelurahan',
        'no_telp',
        'email',
        'jumlah_naker',
        'npwp',
        'status',
    ];

    protected $casts = [
        'jumlah_naker' => 'integer',
    ];

    /**
     * Relasi ke sektor industri perusahaan.
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
