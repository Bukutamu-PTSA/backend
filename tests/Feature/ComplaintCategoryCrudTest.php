<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\CompanySize;
use App\Models\Complainant;
use App\Models\Complaint;
use App\Models\ComplaintCategory;
use App\Models\IndustrialSector;
use Database\Seeders\CompanySizeSeeder;
use Database\Seeders\IndustrialSectorSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ComplaintCategoryCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_lists_categories_ordered_by_name(): void
    {
        ComplaintCategory::create(['category_code' => 'K3', 'category_name' => 'Keselamatan Kerja']);
        ComplaintCategory::create(['category_code' => 'UPAH', 'category_name' => 'Aturan Upah']);

        $this->getJson('/api/complaint-categories')
            ->assertOk()
            ->assertJsonCount(2)
            ->assertJsonPath('0.category_name', 'Aturan Upah')
            ->assertJsonPath('1.category_name', 'Keselamatan Kerja');
    }

    public function test_store_creates_category(): void
    {
        $response = $this->postJson('/api/complaint-categories', [
            'category_code' => 'UPAH_KERJA',
            'category_name' => 'Upah Kerja',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.category_code', 'UPAH_KERJA')
            ->assertJsonPath('data.category_name', 'Upah Kerja')
            ->assertJsonPath('message', 'Kategori aduan berhasil dibuat');

        $this->assertDatabaseHas('complaint_categories', [
            'category_code' => 'UPAH_KERJA',
            'category_name' => 'Upah Kerja',
        ]);
    }

    public function test_store_requires_code_and_name(): void
    {
        $this->postJson('/api/complaint-categories', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['category_code', 'category_name']);
    }

    public function test_store_rejects_duplicate_code(): void
    {
        ComplaintCategory::create(['category_code' => 'UPAH_KERJA', 'category_name' => 'Upah Kerja']);

        $this->postJson('/api/complaint-categories', [
            'category_code' => 'UPAH_KERJA',
            'category_name' => 'Upah Lainnya',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('category_code');
    }

    public function test_show_returns_category(): void
    {
        $category = ComplaintCategory::create(['category_code' => 'K3', 'category_name' => 'Keselamatan Kerja']);

        $this->getJson("/api/complaint-categories/{$category->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $category->id)
            ->assertJsonPath('data.category_code', 'K3')
            ->assertJsonPath('data.category_name', 'Keselamatan Kerja');
    }

    public function test_update_modifies_category(): void
    {
        $category = ComplaintCategory::create(['category_code' => 'K3', 'category_name' => 'Keselamatan Kerja']);

        $this->putJson("/api/complaint-categories/{$category->id}", [
            'category_name' => 'Kesehatan & Keselamatan Kerja',
        ])
            ->assertOk()
            ->assertJsonPath('data.category_name', 'Kesehatan & Keselamatan Kerja')
            ->assertJsonPath('data.category_code', 'K3');

        $this->assertDatabaseHas('complaint_categories', [
            'id' => $category->id,
            'category_name' => 'Kesehatan & Keselamatan Kerja',
        ]);
    }

    public function test_update_allows_keeping_own_code(): void
    {
        $category = ComplaintCategory::create(['category_code' => 'K3', 'category_name' => 'Keselamatan Kerja']);

        $this->putJson("/api/complaint-categories/{$category->id}", [
            'category_code' => 'K3',
            'category_name' => 'Keselamatan Kerja Baru',
        ])->assertOk();
    }

    public function test_update_rejects_duplicate_code_from_another_category(): void
    {
        ComplaintCategory::create(['category_code' => 'UPAH', 'category_name' => 'Upah']);
        $category = ComplaintCategory::create(['category_code' => 'K3', 'category_name' => 'Keselamatan Kerja']);

        $this->putJson("/api/complaint-categories/{$category->id}", [
            'category_code' => 'UPAH',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('category_code');
    }

    public function test_destroy_deletes_unused_category(): void
    {
        $category = ComplaintCategory::create(['category_code' => 'K3', 'category_name' => 'Keselamatan Kerja']);

        $this->deleteJson("/api/complaint-categories/{$category->id}")
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseMissing('complaint_categories', ['id' => $category->id]);
    }

    public function test_destroy_rejects_category_in_use(): void
    {
        $this->seed([IndustrialSectorSeeder::class, CompanySizeSeeder::class]);

        $category = ComplaintCategory::create(['category_code' => 'K3', 'category_name' => 'Keselamatan Kerja']);
        $complainant = Complainant::create([
            'nama_lengkap' => 'Budi', 'nik' => '3201010101010001', 'alamat' => 'Jl. A',
            'jenis_kelamin' => 'laki-laki', 'jabatan' => 'Pekerja', 'no_telp' => '0812', 'email' => 'b@e.com',
        ]);
        $company = Company::create([
            'nama_perusahaan' => 'PT Maju', 'sector_id' => IndustrialSector::first()->id, 'size_id' => CompanySize::first()->id, 'alamat' => 'Jl. B',
            'jumlah_naker' => 10, 'provinsi' => 'Jabar', 'kota_kab' => 'Bekasi',
            'kecamatan' => 'Cikarang', 'kelurahan' => 'Waluya',
            'no_telp' => '0211234567', 'email' => 'pt@maju.com',
        ]);
        Complaint::create([
            'complainant_id' => $complainant->id, 'company_id' => $company->id, 'category_id' => $category->id,
            'ticket_number' => 'TCK-TEST-1', 'description' => 'Uji kategori dipakai aduan.', 'complaint_date' => now(),
        ]);

        $this->deleteJson("/api/complaint-categories/{$category->id}")
            ->assertConflict()
            ->assertJsonPath('success', false);

        $this->assertDatabaseHas('complaint_categories', ['id' => $category->id]);
    }
}
