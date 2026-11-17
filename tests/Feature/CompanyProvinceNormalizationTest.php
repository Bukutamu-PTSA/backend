<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\CompanySize;
use App\Models\IndustrialSector;
use Database\Seeders\CompanySizeSeeder;
use Database\Seeders\IndustrialSectorSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CompanyProvinceNormalizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([
            CompanySizeSeeder::class,
            IndustrialSectorSeeder::class,
        ]);
    }

    public function test_migration_merges_jakarta_variants_and_uppercases_other_provinces(): void
    {
        $jakarta = [
            'DKI', 'dki', 'DKI  Jakarta', 'DKI JAKARTAJ', 'DKI JAKRTA', 'DKI JKt', 'DKI Jakartsa',
            'DKI Jakata', 'DKI Jaklarta', 'DKI jAKARTA', 'DKI jkt', 'Daerah Khusus Jakarta',
            'Jakarta', 'dki jakarta', 'Daerah Khusus Ibukota Jakarta',
        ];

        foreach ([...$jakarta, 'Jawa Barat', ' JAWA  BARAT ', 'Daerah Istimewa Yogyakarta'] as $province) {
            $this->createCompany($province);
        }

        $this->runMigration();

        $this->assertSame([
            'DAERAH ISTIMEWA YOGYAKARTA' => 1,
            'DAERAH KHUSUS IBUKOTA JAKARTA' => count($jakarta),
            'JAWA BARAT' => 2,
        ], $this->provinceCounts());
    }

    public function test_migration_maps_abbreviations_and_city_names_to_their_province(): void
    {
        foreach (['Jabar', 'jateng', 'Jawa Tirmur', 'NTB', 'ntt', 'Denpasar ', 'MARGOREJO', 'Tangerang Selatan'] as $province) {
            $this->createCompany($province);
        }

        $this->runMigration();

        $this->assertSame([
            'BALI' => 1,
            'BANTEN' => 1,
            'JAWA BARAT' => 1,
            'JAWA TENGAH' => 2,
            'JAWA TIMUR' => 1,
            'NUSA TENGGARA BARAT' => 1,
            'NUSA TENGGARA TIMUR' => 1,
        ], $this->provinceCounts());
    }

    private function runMigration(): void
    {
        $migration = require database_path('migrations/2026_11_17_221716_normalize_company_provinces.php');
        $migration->up();
    }

    /**
     * @return array<string, int>
     */
    private function provinceCounts(): array
    {
        return Company::query()
            ->selectRaw('provinsi, COUNT(*) AS total')
            ->groupBy('provinsi')
            ->orderBy('provinsi')
            ->pluck('total', 'provinsi')
            ->map(fn ($total): int => (int) $total)
            ->all();
    }

    private function createCompany(string $province): void
    {
        Company::query()->create([
            'nama_perusahaan' => 'PT Contoh',
            'sector_id' => IndustrialSector::query()->value('id'),
            'size_id' => CompanySize::query()->value('id'),
            'alamat' => 'Jl. Contoh',
            'jumlah_naker' => 10,
            'provinsi' => $province,
            'kota_kab' => 'Kota',
            'kecamatan' => 'Kecamatan',
            'kelurahan' => 'Kelurahan',
            'no_telp' => '08123456789',
            'email' => 'contoh@example.com',
        ]);
    }
}
