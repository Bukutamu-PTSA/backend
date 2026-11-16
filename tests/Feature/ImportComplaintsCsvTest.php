<?php

namespace Tests\Feature;

use App\Http\Resources\ComplaintResource;
use App\Models\Company;
use App\Models\Complainant;
use App\Models\Complaint;
use Database\Seeders\CompanySizeSeeder;
use Database\Seeders\ComplaintCategorySeeder;
use Database\Seeders\IndustrialSectorSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ImportComplaintsCsvTest extends TestCase
{
    use RefreshDatabase;

    private const HEADER = 'Timestamp,Jenis Pengaduan,Nama Pelapor,NIK,Alamat,Jenis Kelamin,Jabatan,No. Telpon,E-mail,Nama Perusahaan,Sektor Industri,Alamat Perusahaan,Jumlah Tenaga Kerja,Provinsi,Kab/Kota,Kecamatan,Kelurahan,No. Telp Perusahaan,E-mail Perusahaan,Deskripsi Aduan';

    private const VALID_ROW = '23/09/2026 13:35:18,Wajib Lapor Ketenagakerjaan Perusahaan (WLKP),Netty Triyanti,3175096202890002,mandar,perempuan,direktur,081212182589,netty@example.com,pt mondar mandir,ayam potong,ciracas,400,dki jakarta,JAKARTA TIMUR (KOTA),ciracas,ciracas,02137687261,netty@example.com,tidak bisa isi bpjs tk npp';

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([
            ComplaintCategorySeeder::class,
            IndustrialSectorSeeder::class,
            CompanySizeSeeder::class,
        ]);
    }

    private function csvFile(string ...$rows): string
    {
        $path = tempnam(sys_get_temp_dir(), 'pengaduan').'.csv';
        file_put_contents($path, implode("\n", [self::HEADER, ...$rows])."\n");

        return $path;
    }

    public function test_it_imports_a_row_into_complainant_company_and_complaint(): void
    {
        $this->artisan('complaints:import-csv', ['path' => $this->csvFile(self::VALID_ROW)])
            ->assertSuccessful();

        $complaint = Complaint::with(['complainant', 'company.size', 'company.sector', 'category'])->sole();

        $this->assertSame('TKT-23-09-2026-001', $complaint->ticket_number);
        $this->assertSame('2026-09-23 13:35:18', $complaint->complaint_date->format('Y-m-d H:i:s'));
        $this->assertSame('WAJIB_LAPOR', $complaint->category->category_code);
        $this->assertSame('tidak bisa isi bpjs tk npp', $complaint->description);
        $this->assertSame('perempuan', $complaint->complainant->jenis_kelamin);
        $this->assertSame('3175096202890002', $complaint->complainant->nik);
        $this->assertSame(400, $complaint->company->jumlah_naker);
        $this->assertSame('LARGE', $complaint->company->size->size_code);
        $this->assertSame('LEGACY_UNSPECIFIED', $complaint->company->sector->sector_code);
    }

    public function test_running_the_import_twice_does_not_duplicate_data(): void
    {
        $path = $this->csvFile(self::VALID_ROW);

        $this->artisan('complaints:import-csv', ['path' => $path])->assertSuccessful();
        $this->artisan('complaints:import-csv', ['path' => $path])->assertSuccessful();

        $this->assertSame(1, Complaint::count());
        $this->assertSame(1, Complainant::count());
        $this->assertSame(1, Company::count());
    }

    public function test_it_skips_rows_with_an_invalid_nik(): void
    {
        $brokenNikRow = str_replace('3175096202890002', '"3,27509E+15"', self::VALID_ROW);

        $this->artisan('complaints:import-csv', ['path' => $this->csvFile($brokenNikRow)])
            ->expectsOutputToContain('NIK tidak valid')
            ->assertSuccessful();

        $this->assertSame(0, Complaint::count());
    }

    public function test_complaint_resource_exposes_the_original_complaint_date(): void
    {
        $this->artisan('complaints:import-csv', ['path' => $this->csvFile(self::VALID_ROW)])
            ->assertSuccessful();

        $payload = (new ComplaintResource(Complaint::sole()))->resolve();

        $this->assertSame('2026-09-23 13:35:18', $payload['complaint_date']);
        $this->assertNotSame($payload['complaint_date'], $payload['created_at']);
    }

    public function test_dry_run_does_not_write_to_the_database(): void
    {
        $this->artisan('complaints:import-csv', ['path' => $this->csvFile(self::VALID_ROW), '--dry-run' => true])
            ->assertSuccessful();

        $this->assertSame(0, Complaint::count());
        $this->assertSame(0, Complainant::count());
    }
}
