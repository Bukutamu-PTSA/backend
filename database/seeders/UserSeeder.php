<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\AdminProfile;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $superAdmin = User::create([
            'username' => 'superadmin',
            'email' => 'superadmin@ptsa.go.id',
            'password' => Hash::make('password123'),
            'role' => 'super_admin',
        ]);

        AdminProfile::create([
            'user_id' => $superAdmin->id,
            'nama_lengkap' => 'Super Administrator PTSA',
            'nama_petugas' => 'Petugas Pusat 01',
            'unit_kerja' => 'Subdit Pengawasan Norma Kerja',
            'golongan' => 'IV/a',
        ]);

        $admin = User::create([
            'username' => 'petugas1',
            'email' => 'petugas1@ptsa.go.id',
            'password' => Hash::make('password123'),
            'role' => 'admin',
        ]);

        AdminProfile::create([
            'user_id' => $admin->id,
            'nama_lengkap' => 'Ahmad Fauzi, S.H.',
            'nama_petugas' => 'Petugas PTSA 02',
            'unit_kerja' => 'Pelayanan Pengaduan Ketenagakerjaan',
            'golongan' => 'III/b',
        ]);
    }
}
