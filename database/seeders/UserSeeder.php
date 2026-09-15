<?php

namespace Database\Seeders;

use App\Models\AdminProfile;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $superAdmin = User::updateOrCreate(
            ['email' => 'superadmin@ptsa.go.id'],
            [
                'username' => 'superadmin',
                'full_name' => 'Super Administrator PTSA',
                'password' => Hash::make('password123'),
                'role' => 'super_admin',
                'status' => 'aktif',
            ],
        );

        AdminProfile::updateOrCreate(
            ['user_id' => $superAdmin->id],
            [
                'nama_lengkap' => 'Super Administrator PTSA',
                'nama_petugas' => 'Petugas Pusat 01',
                'unit_kerja' => 'Subdit Pengawasan Norma Kerja',
                'golongan' => 'IV/a',
            ],
        );

        $admin = User::updateOrCreate(
            ['email' => 'petugas1@ptsa.go.id'],
            [
                'username' => 'petugas1',
                'full_name' => 'Ahmad Fauzi, S.H.',
                'password' => Hash::make('password123'),
                'role' => 'admin',
                'status' => 'aktif',
            ],
        );

        AdminProfile::updateOrCreate(
            ['user_id' => $admin->id],
            [
                'nama_lengkap' => 'Ahmad Fauzi, S.H.',
                'nama_petugas' => 'Petugas PTSA 02',
                'unit_kerja' => 'Pelayanan Pengaduan Ketenagakerjaan',
                'golongan' => 'III/b',
            ],
        );

        $petugas = User::updateOrCreate(
            ['email' => 'petugas2@ptsa.go.id'],
            [
                'username' => 'petugas2',
                'full_name' => 'Agus Wijaya',
                'password' => Hash::make('password123'),
                'role' => 'petugas',
                'status' => 'aktif',
            ],
        );

        AdminProfile::updateOrCreate(
            ['user_id' => $petugas->id],
            [
                'nama_lengkap' => 'Agus Wijaya',
                'nama_petugas' => 'Petugas PTSA 03',
                'unit_kerja' => 'Pelayanan Pengaduan Ketenagakerjaan',
                'golongan' => 'III/a',
            ],
        );
    }
}
