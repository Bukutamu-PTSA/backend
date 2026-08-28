<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AppSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'setting_key',
        'setting_value',
        'setting_type',
        'description',
        'created_by',
    ];

    /**
     * Relasi ke profil admin yang membuat atau memperbarui pengaturan.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(AdminProfile::class, 'created_by');
    }
}
