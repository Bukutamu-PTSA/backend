<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * Label role untuk ditampilkan sebagai badge di UI.
     *
     * @var array<string, string>
     */
    private const ROLE_LABELS = [
        'super_admin' => 'Super Admin',
        'admin' => 'Admin',
        'petugas' => 'Petugas',
    ];

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'username' => $this->username,
            'full_name' => $this->full_name,
            'email' => $this->email,
            'role' => $this->role,
            'role_label' => self::ROLE_LABELS[$this->role] ?? $this->role,
            'status' => $this->status,
            'last_login' => $this->last_login?->format('Y-m-d H:i:s'),
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
        ];
    }
}
