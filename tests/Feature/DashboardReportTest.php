<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Complainant;
use App\Models\Complaint;
use App\Models\ComplaintCategory;
use App\Models\User;
use Database\Seeders\CompanySizeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardReportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(CompanySizeSeeder::class);
    }

    private function createAdmin(): User
    {
        return User::create([
            'username' => 'report_admin',
            'email' => 'report_admin@mail.com',
            'password' => bcrypt('password'),
            'role' => 'admin',
        ]);
    }

    private function createComplaint(
        string $status = 'DIPROSES',
        int $jumlahNaker = 25,
        ?string $complaintDate = null
    ): Complaint {
        $category = ComplaintCategory::firstOrCreate(
            ['category_code' => 'UPAH_KERJA'],
            ['category_name' => 'Upah Kerja']
        );

        $complainant = Complainant::create([
            'nama_lengkap' => 'Budi Santoso',
            'nik' => fake()->unique()->numerify('################'),
            'alamat' => 'Jl. Test No. 1',
            'jenis_kelamin' => 'laki-laki',
            'jabatan' => 'Pekerja',
            'no_telp' => '081234567890',
        ]);

        $company = Company::create([
            'nama_perusahaan' => 'PT Test ' . fake()->unique()->numberBetween(1, 99999),
            'sector_id' => 1,
            'alamat' => 'Jl. Test No. 2',
            'jumlah_naker' => $jumlahNaker,
            'provinsi' => 'Jawa Barat',
            'kota_kab' => 'Bekasi',
            'kecamatan' => 'Cikarang',
            'kelurahan' => 'Waluya',
        ]);

        return Complaint::create([
            'complainant_id' => $complainant->id,
            'company_id' => $company->id,
            'category_id' => $category->id,
            'ticket_number' => 'TCK-TEST-' . fake()->unique()->numerify('######'),
            'description' => 'Pengaduan untuk pengujian dashboard report.',
            'status' => $status,
            'priority' => 'MEDIUM',
            'complaint_date' => $complaintDate ?? '2026-09-02 10:00:00',
        ]);
    }

    public function test_admin_can_view_dashboard_overview(): void
    {
        $admin = $this->createAdmin();

        $this->createComplaint('DIPROSES', 25);
        $this->createComplaint('DIPROSES', 10);

        $response = $this->actingAs($admin)
            ->getJson('/api/v1/dashboard/overview');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.total_complaints', 2)
            ->assertJsonPath('data.complaints_by_status.DIPROSES', 2)
            ->assertJsonPath('data.complaints_by_company_size.Menengah', 1)
            ->assertJsonPath('data.complaints_by_company_size.Kecil', 1);

        $this->assertCount(2, $response->json('data.recent_complaints'));
    }

    public function test_non_admin_cannot_view_dashboard_overview(): void
    {
        $user = User::create([
            'username' => 'regular_user',
            'email' => 'regular@mail.com',
            'password' => bcrypt('password'),
            'role' => 'pelapor',
        ]);

        $this->actingAs($user)
            ->getJson('/api/v1/dashboard/overview')
            ->assertForbidden()
            ->assertJson([
                'success' => false,
                'message' => 'Akses ditolak. Anda tidak memiliki hak akses yang sesuai.',
            ]);
    }

    public function test_report_by_category_can_filter_by_date_range(): void
    {
        $admin = $this->createAdmin();

        $this->createComplaint(
            complaintDate: '2026-09-02 10:00:00'
        );

        $this->createComplaint(
            complaintDate: '2026-09-03 10:00:00'
        );

        $response = $this->actingAs($admin)
            ->getJson('/api/v1/reports/by-category?start_date=2026-09-02&end_date=2026-09-02');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.0.category_id', 1)
            ->assertJsonPath('data.0.total', 1)
            ->assertJsonPath('data.0.category.category_name', 'Upah Kerja');
    }

    public function test_report_by_company_size_returns_complaint_counts_by_size(): void
    {
        $admin = $this->createAdmin();

        $this->createComplaint(jumlahNaker: 25);
        $this->createComplaint(jumlahNaker: 10);
        $this->createComplaint(jumlahNaker: 150);

        $response = $this->actingAs($admin)
            ->getJson('/api/v1/reports/by-company-size');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.Mikro', 0)
            ->assertJsonPath('data.Kecil', 1)
            ->assertJsonPath('data.Menengah', 1)
            ->assertJsonPath('data.Besar', 1);
    }

    public function test_export_csv_returns_complaints(): void
    {
        $admin = $this->createAdmin();

        $this->createComplaint();

        $response = $this->actingAs($admin)
            ->get('/api/v1/reports/export/csv');

        $response->assertOk()
            ->assertHeader('Content-Type', 'text/csv; charset=utf-8');

        $content = $response->streamedContent();

        $this->assertStringContainsString('Nomor Tiket', $content);
        $this->assertStringContainsString('Deskripsi', $content);
        $this->assertStringContainsString('DIPROSES', $content);
        $this->assertStringContainsString('Pengaduan untuk pengujian dashboard report.', $content);
    }

    public function test_export_csv_can_filter_by_status(): void
    {
        $admin = $this->createAdmin();

        $processed = $this->createComplaint('DIPROSES');
        $completed = $this->createComplaint('SELESAI');

        $response = $this->actingAs($admin)
            ->get('/api/v1/reports/export/csv?status=DIPROSES');

        $response->assertOk();

        $content = $response->streamedContent();

        $this->assertStringContainsString($processed->ticket_number, $content);
        $this->assertStringNotContainsString($completed->ticket_number, $content);
    }

    public function test_export_csv_can_filter_by_date_range(): void
    {
        $admin = $this->createAdmin();

        $included = $this->createComplaint(
            complaintDate: '2026-09-02 10:00:00'
        );

        $excluded = $this->createComplaint(
            complaintDate: '2026-09-03 10:00:00'
        );

        $response = $this->actingAs($admin)
            ->get('/api/v1/reports/export/csv?start_date=2026-09-02&end_date=2026-09-02');

        $response->assertOk();

        $content = $response->streamedContent();

        $this->assertStringContainsString($included->ticket_number, $content);
        $this->assertStringNotContainsString($excluded->ticket_number, $content);
    }

    public function test_report_endpoints_require_authentication(): void
    {
        $this->getJson('/api/v1/dashboard/overview')
            ->assertUnauthorized();

        $this->getJson('/api/v1/reports/by-category')
            ->assertUnauthorized();

        $this->getJson('/api/v1/reports/by-company-size')
            ->assertUnauthorized();

        $this->get('/api/v1/reports/export/csv')
            ->assertUnauthorized();
    }
}