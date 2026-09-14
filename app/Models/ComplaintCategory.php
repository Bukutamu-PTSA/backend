<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ComplaintCategory extends Model
{
    use HasFactory;

    protected $fillable = [
        'category_code',
        'category_name',
        'description',
        'icon',
        'color',
        'status',
    ];

    /**
     * Relasi ke seluruh tiket pengaduan yang berada dalam kategori ini.
     */
    public function complaints(): HasMany
    {
        return $this->hasMany(Complaint::class, 'category_id');
    }
}
