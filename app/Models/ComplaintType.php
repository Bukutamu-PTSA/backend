<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ComplaintType extends Model
{
    use HasFactory;

    protected $fillable = [
        'type_code',
        'type_name',
        'description',
    ];

    /**
     * Relasi ke seluruh tiket pengaduan yang berjenis ini.
     */
    public function complaints(): HasMany
    {
        return $this->hasMany(Complaint::class, 'complaint_type_id');
    }
}
