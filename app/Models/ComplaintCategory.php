<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ComplaintCategory extends Model
{
    use HasFactory;

    /**
     * Kode kategori "Lainnya" yang jenis pengaduannya diisi bebas oleh pelapor.
     */
    public const OTHER_CODE = 'LAINNYA';

    protected $fillable = [
        'category_code',
        'category_name',
        'description',
    ];

    /**
     * Apakah kategori ini kategori "Lainnya".
     */
    public function isOther(): bool
    {
        return $this->category_code === self::OTHER_CODE;
    }

    /**
     * Apakah ID kategori yang diberikan adalah kategori "Lainnya".
     */
    public static function isOtherId(int|string|null $categoryId): bool
    {
        if (blank($categoryId)) {
            return false;
        }

        return static::query()
            ->whereKey($categoryId)
            ->where('category_code', self::OTHER_CODE)
            ->exists();
    }

    /**
     * Relasi ke seluruh tiket pengaduan yang berada dalam kategori ini.
     */
    public function complaints(): HasMany
    {
        return $this->hasMany(Complaint::class, 'category_id');
    }
}
