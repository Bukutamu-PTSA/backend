<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class IndustrialSector extends Model
{
    use HasFactory;

    protected $fillable = [
        'sector_code',
        'sector_name',
        'description',
    ];

    public function companies(): HasMany
    {
        return $this->hasMany(Company::class, 'sector_id');
    }
}
