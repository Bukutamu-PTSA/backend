<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\CompanySize;
use App\Models\Complainant;
use App\Models\Complaint;
use App\Models\ComplaintCategory;
use App\Models\User;
use Database\Seeders\IndustrialSectorSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ComplaintStoreTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(IndustrialSectorSeeder::class);
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'category_id' => 1,
            'description' => 'Perusahaan tidak membayar upah sejak bulan Juli padahal sudah ditagih berkali-kali.',
            'complainant' => [
                'nama_lengkap' => 'Budi Santoso', 'nik' => '3201010101010001', 'alamat' => 'Jl. A No.1',
                'jenis_kelamin' => 'laki-laki', 'jabatan' => 'Pekerja', 'no_telp' => '081234567890',
            ],
            'company' => [
                'nama_perusahaan' => 'PT Maju Bersama', 'sector_id' => 1, 'alamat' => 'Jl. B',
                'jumlah_naker' => 120, 'provinsi' => 'Jawa Barat', 'kota_kab' => 'Bekasi',
                'kecamatan' => 'Cikarang', 'kelurahan' => 'Waluya',
            ],
        ], $overrides);
    }

    public function test_single_manual_post_creates_complaint_with_photos(): void
    {
        $user = User::create([
            'username' => 'tester', 'email' => 'tester@mail.com',
            'password' => bcrypt('password'), 'role' => 'admin',
        ]);
        ComplaintCategory::create(['category_code' => 'K3', 'category_name' => 'Keselamatan Kerja']);

        $response = $this->actingAs($user)->postJson('/api/complaints', $this->payload([
            'attachments' => [
                UploadedFile::fake()->create('bukti1.jpg', 50),
                UploadedFile::fake()->create('bukti2.png', 50),
            ],
        ]));

        $response->assertStatus(201)->assertJsonCount(2, 'data.attachments');

        $this->assertSame(1, Complainant::count());
        $this->assertSame(1, Company::count());
        $this->assertSame(1, Complaint::count());
    }

    public function test_screenshot_payload_accepts_category_and_creates_complaint(): void
    {
        ComplaintCategory::create(['category_code' => 'UPAH_KERJA', 'category_name' => 'Upah Kerja']);

        $response = $this->postJson('/api/complaints', [
            'category_id' => 1,
            'description' => 'Perusahaan melakukan pelanggaran terhadap hak pekerja.',
            'complainant' => [
                'nama_lengkap' => 'Siti Aminah',
                'nik' => '3201010101010002',
                'alamat' => 'Jl. Merdeka No. 2',
                'jenis_kelamin' => 'perempuan',
                'jabatan' => 'Operator',
                'no_telp' => '081234567891',
            ],
            'company' => [
                'nama_perusahaan' => 'PT Sejahtera',
                'sector_id' => 1,
                'alamat' => 'Jl. Industri No. 2',
                'jumlah_naker' => 25,
                'provinsi' => 'Jawa Barat',
                'kota_kab' => 'Bekasi',
                'kecamatan' => 'Cikarang',
                'kelurahan' => 'Waluya',
            ],
        ]);

        $response->assertCreated()
            ->assertJsonMissingPath('data.title')
            ->assertJsonPath('data.priority', 'MEDIUM')
            ->assertJsonPath('data.category.id', 1)
            ->assertJsonPath('data.category.category_name', 'Upah Kerja')
            ->assertJsonPath('data.company.sector.id', 1)
            ->assertJsonPath('data.company.sector.sector_code', 'AGRICULTURE');

        $this->assertDatabaseHas('complaints', [
            'priority' => 'MEDIUM',
            'category_id' => 1,
        ]);
    }

    public function test_master_endpoints_return_categories_and_industrial_sectors(): void
    {
        ComplaintCategory::create(['category_code' => 'UPAH_KERJA', 'category_name' => 'Upah Kerja']);

        $this->getJson('/api/complaint-categories')
            ->assertOk()
            ->assertJsonPath('0.category_code', 'UPAH_KERJA')
            ->assertJsonPath('0.category_name', 'Upah Kerja');

        $this->getJson('/api/industrial-sectors')
            ->assertOk()
            ->assertJsonCount(10)
            ->assertJsonPath('0.sector_code', 'AGRICULTURE')
            ->assertJsonPath('0.sector_name', 'Pertanian, Kehutanan, dan Perikanan');
    }

    public function test_complaint_index_filters_and_paginates(): void
    {
        $user = User::create([
            'username' => 'filterer', 'email' => 'filterer@mail.com',
            'password' => bcrypt('password'), 'role' => 'admin',
        ]);
        ComplaintCategory::create(['category_code' => 'UPAH_KERJA', 'category_name' => 'Upah Kerja']);
        ComplaintCategory::create(['category_code' => 'K3', 'category_name' => 'Keselamatan Kerja']);

        foreach ([
            ['nik' => '3201010101010002', 'name' => 'Budi Alpha', 'company' => 'PT Alpha', 'category_id' => 1],
            ['nik' => '3201010101010003', 'name' => 'Siti Beta', 'company' => 'PT Beta', 'category_id' => 2],
            ['nik' => '3201010101010004', 'name' => 'Rina Gamma', 'company' => 'PT Gamma', 'category_id' => 1],
        ] as $item) {
            $this->postJson('/api/complaints', $this->payload([
                'category_id' => $item['category_id'],
                'complainant' => [
                    'nama_lengkap' => $item['name'], 'nik' => $item['nik'], 'alamat' => 'Jl. Filter',
                    'jenis_kelamin' => 'perempuan', 'jabatan' => 'Pekerja', 'no_telp' => '081234567899',
                ],
                'company' => [
                    'nama_perusahaan' => $item['company'], 'sector_id' => 1, 'alamat' => 'Jl. Company',
                    'jumlah_naker' => 20, 'provinsi' => 'Jawa Barat', 'kota_kab' => 'Bekasi',
                    'kecamatan' => 'Cikarang', 'kelurahan' => 'Waluya',
                ],
            ]))->assertCreated();
        }

        $complaints = Complaint::orderBy('id')->get();
        $complaints[0]->update(['complaint_date' => '2026-08-27', 'status' => 'DIPROSES']);
        $complaints[1]->update(['complaint_date' => '2026-08-15', 'status' => 'SELESAI']);
        $complaints[2]->update(['complaint_date' => '2026-07-02', 'status' => 'DIPROSES']);

        $this->actingAs($user)
            ->getJson('/api/complaints?search=Alpha&month=8&year=2026&per_page=10')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.complainant.nama_lengkap', 'Budi Alpha')
            ->assertJsonPath('meta.total', 1);

        $this->actingAs($user)
            ->getJson('/api/complaints?category_id=2&status=SELESAI&month=8&year=2026')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.complainant.nama_lengkap', 'Siti Beta');

        $this->actingAs($user)
            ->getJson('/api/complaints?per_page=2&page=2')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('meta.total', 3)
            ->assertJsonPath('meta.per_page', 2)
            ->assertJsonPath('meta.current_page', 2)
            ->assertJsonPath('meta.last_page', 2);

        $this->actingAs($user)
            ->getJson('/api/complaints?month=13')
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['month']);
    }

    public function test_category_is_required_when_creating_complaint(): void
    {
        $response = $this->postJson('/api/complaints', [
            'description' => 'Perusahaan melakukan pelanggaran terhadap hak pekerja.',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['category_id', 'complainant.nama_lengkap', 'company.nama_perusahaan']);
    }

    public function test_same_nik_reuses_complainant(): void
    {
        $user = User::create([
            'username' => 'tester', 'email' => 'tester@mail.com',
            'password' => bcrypt('password'), 'role' => 'admin',
        ]);
        ComplaintCategory::create(['category_code' => 'K3', 'category_name' => 'Keselamatan Kerja']);

        $this->actingAs($user)->postJson('/api/complaints', $this->payload());
        $this->actingAs($user)->postJson('/api/complaints', $this->payload());

        $this->assertSame(1, Complainant::count());
        $this->assertSame(2, Complaint::count());
    }

    public function test_dashboard_summary_counts_complaints_by_category(): void
    {
        $user = User::create([
            'username' => 'dashboard', 'email' => 'dashboard@mail.com',
            'password' => bcrypt('password'), 'role' => 'admin',
        ]);
        ComplaintCategory::create(['category_code' => 'UPAH_KERJA', 'category_name' => 'Upah Kerja']);
        ComplaintCategory::create(['category_code' => 'K3', 'category_name' => 'Keselamatan Kerja']);

        $this->postJson('/api/complaints', $this->payload());
        $this->postJson('/api/complaints', $this->payload([
            'category_id' => 2,
            'complainant' => [
                'nama_lengkap' => 'Siti Aminah', 'nik' => '3201010101010002', 'alamat' => 'Jl. C',
                'jenis_kelamin' => 'perempuan', 'jabatan' => 'Operator', 'no_telp' => '081234567891',
            ],
        ]));
        $this->postJson('/api/complaints', $this->payload());
        Complaint::latest('id')->first()->update(['category_id' => null]);

        $response = $this->actingAs($user)->getJson('/api/dashboard/complaint-summary');

        $response->assertOk()
            ->assertJsonPath('meta.total', 3)
            ->assertJsonPath('meta.uncategorized_count', 1)
            ->assertJsonPath('data.0.category_code', 'UPAH_KERJA')
            ->assertJsonPath('data.0.count', 1)
            ->assertJsonPath('data.1.category_code', 'K3')
            ->assertJsonPath('data.1.count', 1);
    }

    public function test_dashboard_summary_can_filter_complaints_for_today(): void
    {
        $user = User::create([
            'username' => 'today-dashboard', 'email' => 'today-dashboard@mail.com',
            'password' => bcrypt('password'), 'role' => 'admin',
        ]);
        ComplaintCategory::create(['category_code' => 'UPAH_KERJA', 'category_name' => 'Upah Kerja']);
        ComplaintCategory::create(['category_code' => 'K3', 'category_name' => 'Keselamatan Kerja']);

        $this->postJson('/api/complaints', $this->payload());
        $this->postJson('/api/complaints', $this->payload(['category_id' => 2]));
        $this->postJson('/api/complaints', $this->payload(['category_id' => 1]));

        $complaints = Complaint::orderBy('id')->get();
        $complaints[0]->update(['complaint_date' => now()]);
        $complaints[1]->update(['complaint_date' => now()->subDay()]);
        $complaints[2]->update(['complaint_date' => now(), 'category_id' => null]);

        $response = $this->actingAs($user)->getJson('/api/dashboard/complaint-summary?period=today');

        $response->assertOk()
            ->assertJsonPath('meta.period', 'today')
            ->assertJsonPath('meta.total', 2)
            ->assertJsonPath('meta.uncategorized_count', 1)
            ->assertJsonPath('data.0.category_code', 'UPAH_KERJA')
            ->assertJsonPath('data.0.count', 1)
            ->assertJsonPath('data.1.category_code', 'K3')
            ->assertJsonPath('data.1.count', 0);

        $this->actingAs($user)
            ->getJson('/api/dashboard/complaint-summary?period=tomorrow')
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['period']);
    }

    public function test_dashboard_statistics_returns_cards_and_chart_data(): void
    {
        $user = User::create([
            'username' => 'statistics-dashboard', 'email' => 'statistics-dashboard@mail.com',
            'password' => bcrypt('password'), 'role' => 'admin',
        ]);
        ComplaintCategory::create(['category_code' => 'UPAH_KERJA', 'category_name' => 'Upah Kerja']);
        ComplaintCategory::create(['category_code' => 'K3', 'category_name' => 'Keselamatan Kerja']);
        CompanySize::create(['size_code' => 'MICRO', 'size_name' => 'Mikro', 'min_employees' => 1, 'max_employees' => 4]);
        CompanySize::create(['size_code' => 'LARGE', 'size_name' => 'Besar', 'min_employees' => 100, 'max_employees' => null]);

        $this->postJson('/api/complaints', $this->payload())->assertCreated();
        $this->postJson('/api/complaints', $this->payload(['category_id' => 2]))->assertCreated();
        $complaint = Complaint::latest('id')->firstOrFail();
        $complaint->update(['status' => 'SELESAI']);

        $response = $this->actingAs($user)->getJson('/api/dashboard/statistics?period=today');

        $response->assertOk()
            ->assertJsonPath('data.total_complaints', 2)
            ->assertJsonPath('data.completed_complaints', 1)
            ->assertJsonPath('data.satisfaction_index', null)
            ->assertJsonPath('data.satisfaction_responses', 0)
            ->assertJsonPath('data.by_company_size.0.size_code', 'MICRO')
            ->assertJsonPath('data.by_company_size.0.count', 0)
            ->assertJsonPath('data.by_company_size.1.size_code', 'LARGE')
            ->assertJsonPath('data.by_company_size.1.count', 2)
            ->assertJsonPath('data.by_category.0.count', 1)
            ->assertJsonPath('data.by_category.1.count', 1)
            ->assertJsonPath('meta.period', 'today');
    }

    public function test_authenticated_user_can_download_complaint_pdf(): void
    {
        $user = User::create([
            'username' => 'pdf-user', 'email' => 'pdf-user@mail.com',
            'password' => bcrypt('password'), 'role' => 'admin',
        ]);
        ComplaintCategory::create(['category_code' => 'K3', 'category_name' => 'Keselamatan Kerja']);

        $this->postJson('/api/complaints', $this->payload())->assertCreated();
        $complaint = Complaint::firstOrFail();
        $path = 'complaints/'.$complaint->id.'/attachment.png';

        Storage::fake('public');
        Storage::disk('public')->put($path, base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII='));
        $complaint->attachments()->create([
            'file_name' => 'attachment.png',
            'file_path' => $path,
            'file_type' => 'photo',
            'file_size' => 68,
            'mime_type' => 'image/png',
        ]);

        $response = $this->actingAs($user)->get('/api/complaints/'.$complaint->id.'/pdf');

        $response->assertOk()
            ->assertHeader('Content-Type', 'application/pdf')
            ->assertHeader('Content-Disposition', 'attachment; filename="complaint-'.$complaint->id.'.pdf"');
        $this->assertStringStartsWith('%PDF-', $response->getContent());
        $this->assertStringContainsString('/Count 2', $response->getContent());
    }

    public function test_unauthenticated_returns_json_401(): void
    {
        $this->getJson('/api/complaints')
            ->assertStatus(401)
            ->assertJson(['message' => 'Unauthenticated. Token tidak valid.']);

        $this->getJson('/api/dashboard/complaint-summary')->assertStatus(401);
    }

    public function test_wilayah_and_submit_work_without_login(): void
    {
        ComplaintCategory::create(['category_code' => 'K3', 'category_name' => 'Keselamatan Kerja']);

        $this->getJson('/api/provinces')->assertOk();

        $this->postJson('/api/complaints', $this->payload())
            ->assertStatus(201);

        $this->assertSame(1, Complaint::count());

        $this->getJson('/api/complaints')->assertStatus(401);
    }

    public function test_status_change_creates_tracking_log(): void
    {
        $user = User::create([
            'username' => 'tester', 'email' => 'tester@mail.com',
            'password' => bcrypt('password'), 'role' => 'admin',
        ]);
        ComplaintCategory::create(['category_code' => 'K3', 'category_name' => 'Keselamatan Kerja']);

        $this->actingAs($user)->postJson('/api/complaints', $this->payload());
        $id = Complaint::first()->id;

        $response = $this->actingAs($user)->putJson("/api/complaints/$id", [
            'status' => 'SELESAI',
            'notes' => 'Upah sudah dibayar lunas',
        ]);

        $response->assertOk()->assertJsonCount(1, 'data.updates');
        $this->assertSame('SELESAI', Complaint::first()->status);
        $this->assertSame('DIPROSES', Complaint::first()->updates()->first()->old_status);
        $this->assertSame('SELESAI', Complaint::first()->updates()->first()->new_status);

        $this->actingAs($user)->putJson("/api/complaints/$id", [
            'description' => 'Perusahaan sudah membayar seluruh upah pekerja.',
        ]);
        $this->assertSame(1, Complaint::first()->updates()->count());
    }
}
