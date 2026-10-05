<?php

namespace Tests\Feature;

use App\Models\Complainant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ComplainantListTest extends TestCase
{
    use RefreshDatabase;

    private function createUser(string $role = 'pelapor'): User
    {
        return User::create([
            'username' => 'complainant_'.$role,
            'email' => 'complainant_'.$role.'@mail.com',
            'password' => bcrypt('password'),
            'role' => $role,
        ]);
    }

    private function createComplainant(string $nik, string $name): Complainant
    {
        return Complainant::create([
            'nama_lengkap' => $name,
            'nik' => $nik,
            'alamat' => 'Jl. Test No. 1',
            'jenis_kelamin' => 'laki-laki',
            'jabatan' => 'Pekerja',
            'no_telp' => '081234567890',
            'email' => 'pelapor@mail.com',
        ]);
    }

    public function test_index_requires_authentication(): void
    {
        $this->getJson('/api/v1/complainants')->assertUnauthorized();
    }

    public function test_index_lists_complainants_paginated(): void
    {
        $this->createComplainant('3201010101010001', 'Budi Santoso');
        $this->createComplainant('3201010101010002', 'Siti Aminah');

        $response = $this->actingAs($this->createUser())->getJson('/api/v1/complainants');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(2, 'data.data')
            ->assertJsonPath('data.data.0.nama_lengkap', 'Siti Aminah')
            ->assertJsonPath('data.data.1.nama_lengkap', 'Budi Santoso')
            ->assertJsonPath('data.total', 2);
    }

    public function test_index_can_search_by_name_or_nik(): void
    {
        $this->createComplainant('3201010101010001', 'Budi Santoso');
        $this->createComplainant('3201010101010002', 'Siti Aminah');

        $user = $this->createUser();

        $this->actingAs($user)->getJson('/api/v1/complainants?search=Siti')
            ->assertOk()
            ->assertJsonCount(1, 'data.data')
            ->assertJsonPath('data.data.0.nik', '3201010101010002');

        $this->actingAs($user)->getJson('/api/v1/complainants?search=3201010101010001')
            ->assertOk()
            ->assertJsonCount(1, 'data.data')
            ->assertJsonPath('data.data.0.nama_lengkap', 'Budi Santoso');
    }
}
