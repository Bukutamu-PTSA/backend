<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class RoleController extends Controller
{
    /**
     * Daftar role yang tersedia untuk keperluan manajemen/assign user.
     *
     * Role disimpan sebagai enum pada tabel users, sehingga daftar ini
     * bersumber dari nilai enum tersebut, bukan tabel roles terpisah.
     */
    public function index(): JsonResponse
    {
        $roles = [
            ['value' => 'super_admin', 'label' => 'Super Administrator'],
            ['value' => 'admin', 'label' => 'Administrator / Petugas'],
            ['value' => 'pelapor', 'label' => 'Pelapor'],
        ];

        return response()->json([
            'success' => true,
            'data' => $roles,
        ]);
    }
}
