<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CompanySize extends Model
{
    use HasFactory;

    protected $fillable = [
        'size_code',
        'size_name',
        'description',
        'min_employees',
        'max_employees',
        'range_start',
        'range_end',
    ];

    protected $casts = [
        'min_employees' => 'integer',
        'max_employees' => 'integer',
        'range_start' => 'decimal:2',
        'range_end' => 'decimal:2',
    ];

    /**
     * Relasi ke seluruh data perusahaan yang masuk dalam skala ukuran ini.
     */
    public function companies(): HasMany
    {
        return $this->hasMany(Company::class, 'size_id');
    }
}
