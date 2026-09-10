<?php

namespace Tests\Feature;

use App\Models\Attachment;
use App\Models\Company;
use App\Models\CompanySize;
use App\Models\Complainant;
use App\Models\Complaint;
use App\Models\ComplaintCategory;
use App\Models\User;
use Database\Seeders\CompanySizeSeeder;
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
        $this->seed([
            IndustrialSectorSeeder::class,
            CompanySizeSeeder::class,
        ]);
    }

    private function payload(array $overrides = []): array
    {
        return array_replace_recursive([
            'category_id' => 1,
            'description' => 'Perusahaan tidak membayar upah sejak bulan Juli padahal sudah ditagih berkali-kali.',
            'complainant' => [
                'nama_lengkap' => 'Budi Santoso', 'nik' => '3201010101010001', 'alamat' => 'Jl. A No.1',
                'jenis_kelamin' => 'laki-laki', 'jabatan' => 'Pekerja', 'no_telp' => '081234567890',
                'email' => 'budi@example.com',
            ],
            'company' => [
                'nama_perusahaan' => 'PT Maju Bersama', 'sector_id' => 1, 'alamat' => 'Jl. B',
                'jumlah_naker' => 120, 'provinsi' => 'Jawa Barat', 'kota_kab' => 'Bekasi',
                'kecamatan' => 'Cikarang', 'kelurahan' => 'Waluya', 'no_telp' => '0211234567',
                'email' => 'company@example.com',
            ],
        ], $overrides);
    }

    public function test_complaint_relations_use_existing_foreign_keys(): void
    {
        $complaint = new Complaint;

        $this->assertSame('complainant_id', $complaint->complainant()->getForeignKeyName());
        $this->assertSame('company_id', $complaint->company()->getForeignKeyName());
        $this->assertSame('category_id', $complaint->category()->getForeignKeyName());
        $this->assertSame('complaint_id', $complaint->attachments()->getForeignKeyName());
        $this->assertSame('complaint_id', (new Attachment)->complaint()->getForeignKeyName());
    }

    public function test_employee_count_automatically_sets_company_size(): void
    {
        ComplaintCategory::create(['category_code' => 'K3', 'category_name' => 'Keselamatan Kerja']);

        foreach ([
            1 => 'MICRO',
            4 => 'MICRO',
            5 => 'SMALL',
            19 => 'SMALL',
            20 => 'MEDIUM',
            99 => 'MEDIUM',
            100 => 'LARGE',
        ] as $employeeCount => $expectedSizeCode) {
            $response = $this->postJson('/api/complaints', $this->payload([
                'complainant' => [
                    'nik' => str_pad((string) $employeeCount, 16, '0', STR_PAD_LEFT),
                ],
                'company' => [
                    'nama_perusahaan' => "PT {$employeeCount}",
                    'jumlah_naker' => $employeeCount,
                ],
            ]));

            $response->assertCreated()
                ->assertJsonPath('data.company.size.size_code', $expectedSizeCode);

            $this->assertDatabaseHas('companies', [
                'nama_perusahaan' => "PT {$employeeCount}",
                'jumlah_naker' => $employeeCount,
                'size_id' => CompanySize::where('size_code', $expectedSizeCode)->value('id'),
            ]);
        }
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
                'email' => 'siti@example.com',
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
                'no_telp' => '0217654321',
                'email' => 'sejahtera@example.com',
            ],
        ]);

        $response->assertCreated()
            ->assertJsonMissingPath('data.title')
            ->assertJsonMissingPath('data.priority')
            ->assertJsonPath('data.category.id', 1)
            ->assertJsonPath('data.category.category_name', 'Upah Kerja')
            ->assertJsonPath('data.company.sector.id', 1)
            ->assertJsonPath('data.company.sector.sector_code', 'AGRICULTURE');

        $this->assertDatabaseHas('complaints', [
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
        $complaints[0]->update(['complaint_date' => '2026-08-27']);
        $complaints[1]->update(['complaint_date' => '2026-08-15']);
        $complaints[2]->update(['complaint_date' => '2026-07-02']);

        $this->actingAs($user)
            ->getJson('/api/complaints?search=Alpha&month=8&year=2026&per_page=10')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.complainant.nama_lengkap', 'Budi Alpha')
            ->assertJsonPath('meta.total', 1);

        $this->actingAs($user)
            ->getJson('/api/complaints?category_id=2&month=8&year=2026')
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

    public function test_dashboard_province_summary_counts_complaints_and_filters_today(): void
    {
        ComplaintCategory::create(['category_code' => 'UPAH_KERJA', 'category_name' => 'Upah Kerja']);

        $this->postJson('/api/complaints', $this->payload())->assertCreated();
        $this->postJson('/api/complaints', $this->payload([
            'complainant' => [
                'nama_lengkap' => 'Siti Aminah', 'nik' => '3201010101010002', 'alamat' => 'Jl. C',
                'jenis_kelamin' => 'perempuan', 'jabatan' => 'Operator', 'no_telp' => '081234567891',
            ],
            'company' => [
                'nama_perusahaan' => 'PT Jakarta', 'sector_id' => 1, 'alamat' => 'Jl. D',
                'jumlah_naker' => 20, 'provinsi' => 'DKI Jakarta', 'kota_kab' => 'Jakarta Utara',
                'kecamatan' => 'Koja', 'kelurahan' => 'Lagoa',
            ],
        ]))->assertCreated();
        $this->postJson('/api/complaints', $this->payload())->assertCreated();

        $complaints = Complaint::orderBy('id')->get();
        $complaints[0]->update(['complaint_date' => now()]);
        $complaints[1]->update(['complaint_date' => now()]);
        $complaints[2]->update(['complaint_date' => now()->subDay()]);

        $this->getJson('/api/dashboard/province-summary')
            ->assertOk()
            ->assertJsonPath('meta.period', 'all')
            ->assertJsonPath('meta.total_complaints', 3)
            ->assertJsonPath('meta.total_rows', 2)
            ->assertJsonPath('data.0.provinsi', 'Jawa Barat')
            ->assertJsonPath('data.0.count', 2)
            ->assertJsonPath('data.1.provinsi', 'DKI Jakarta')
            ->assertJsonPath('data.1.count', 1);

        $this->getJson('/api/dashboard/province-summary?period=today')
            ->assertOk()
            ->assertJsonPath('meta.period', 'today')
            ->assertJsonPath('meta.total_complaints', 2)
            ->assertJsonPath('meta.total_rows', 2)
            ->assertJsonPath('data.0.count', 1)
            ->assertJsonPath('data.1.count', 1);
    }

    public function test_province_summary_supports_limit_search_pagination_and_category_matrix(): void
    {
        ComplaintCategory::create(['category_code' => 'UPAH_KERJA', 'category_name' => 'Upah Kerja']);
        ComplaintCategory::create(['category_code' => 'K3', 'category_name' => 'Keselamatan Kerja']);

        $provinceComplaints = [
            ['Jawa Barat', 1, 4],
            ['Jawa Timur', 1, 2],
            ['Jawa Tengah', 2, 3],
            ['Bali', 1, 1],
        ];

        $nik = 1;
        foreach ($provinceComplaints as [$provinsi, $categoryId, $count]) {
            for ($i = 0; $i < $count; $i++) {
                $this->postJson('/api/complaints', $this->payload([
                    'category_id' => $categoryId,
                    'complainant' => [
                        'nama_lengkap' => "Pelapor {$provinsi} {$i}",
                        'nik' => str_pad((string) $nik++, 16, '0', STR_PAD_LEFT),
                        'alamat' => 'Jl. Uji', 'jenis_kelamin' => 'laki-laki',
                        'jabatan' => 'Staf', 'no_telp' => '081200000000',
                    ],
                    'company' => [
                        'nama_perusahaan' => "PT {$provinsi} {$i}", 'sector_id' => 1, 'alamat' => 'Jl. Co',
                        'jumlah_naker' => 10, 'provinsi' => $provinsi, 'kota_kab' => 'Kota',
                        'kecamatan' => 'Kec', 'kelurahan' => 'Kel',
                    ],
                ]))->assertCreated();
            }
        }

        $this->getJson('/api/dashboard/province-summary?limit=2')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('meta.limit', 2)
            ->assertJsonPath('meta.total_rows', 4)
            ->assertJsonPath('meta.total_complaints', 10)
            ->assertJsonPath('data.0.provinsi', 'Jawa Barat')
            ->assertJsonPath('data.0.count', 4)
            ->assertJsonPath('data.1.provinsi', 'Jawa Tengah')
            ->assertJsonPath('data.1.count', 3);

        $this->getJson('/api/dashboard/province-summary?search=jawa&sort=provinsi&direction=asc')
            ->assertOk()
            ->assertJsonCount(3, 'data')
            ->assertJsonPath('meta.total_rows', 3)
            ->assertJsonPath('data.0.provinsi', 'Jawa Barat')
            ->assertJsonPath('data.1.provinsi', 'Jawa Tengah')
            ->assertJsonPath('data.2.provinsi', 'Jawa Timur');

        $this->getJson('/api/dashboard/province-summary?per_page=2&page=2&sort=provinsi&direction=asc')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('meta.page', 2)
            ->assertJsonPath('meta.per_page', 2)
            ->assertJsonPath('meta.last_page', 2)
            ->assertJsonPath('data.0.provinsi', 'Jawa Tengah')
            ->assertJsonPath('data.1.provinsi', 'Jawa Timur');

        $this->getJson('/api/dashboard/province-summary?with_categories=1&sort=provinsi&direction=asc')
            ->assertOk()
            ->assertJsonPath('categories.0.code', 'UPAH_KERJA')
            ->assertJsonPath('categories.1.code', 'K3')
            ->assertJsonPath('data.0.provinsi', 'Bali')
            ->assertJsonPath('data.0.category_counts.UPAH_KERJA', 1)
            ->assertJsonPath('data.0.category_counts.K3', 0)
            ->assertJsonPath('data.2.provinsi', 'Jawa Tengah')
            ->assertJsonPath('data.2.category_counts.UPAH_KERJA', 0)
            ->assertJsonPath('data.2.category_counts.K3', 3);

        $this->getJson('/api/dashboard/province-summary?sort=category&category_id=2&direction=desc')
            ->assertOk()
            ->assertJsonPath('data.0.provinsi', 'Jawa Tengah')
            ->assertJsonPath('data.0.count', 3);

        $this->getJson('/api/dashboard/province-summary?sort=category')
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['category_id']);
    }

    public function test_dashboard_statistics_returns_cards_and_chart_data(): void
    {
        $user = User::create([
            'username' => 'statistics-dashboard', 'email' => 'statistics-dashboard@mail.com',
            'password' => bcrypt('password'), 'role' => 'admin',
        ]);
        ComplaintCategory::create(['category_code' => 'UPAH_KERJA', 'category_name' => 'Upah Kerja']);
        ComplaintCategory::create(['category_code' => 'K3', 'category_name' => 'Keselamatan Kerja']);

        $this->postJson('/api/complaints', $this->payload())->assertCreated();
        $this->postJson('/api/complaints', $this->payload(['category_id' => 2]))->assertCreated();

        $response = $this->actingAs($user)->getJson('/api/dashboard/statistics?period=today');

        $response->assertOk()
            ->assertJsonPath('data.total_complaints', 2)
            ->assertJsonMissingPath('data.completed_complaints')
            ->assertJsonPath('data.satisfaction_index', null)
            ->assertJsonPath('data.satisfaction_responses', 0)
            ->assertJsonPath('data.by_company_size.0.size_code', 'MICRO')
            ->assertJsonPath('data.by_company_size.0.count', 0)
            ->assertJsonPath('data.by_company_size.3.size_code', 'LARGE')
            ->assertJsonPath('data.by_company_size.3.count', 2)
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

    public function test_complaint_endpoints_are_public(): void
    {
        $this->getJson('/api/complaints')->assertOk();
        $this->getJson('/api/dashboard/complaint-summary')->assertOk();
    }

    public function test_wilayah_and_submit_work_without_login(): void
    {
        ComplaintCategory::create(['category_code' => 'K3', 'category_name' => 'Keselamatan Kerja']);

        $this->getJson('/api/provinces')->assertOk();

        $this->postJson('/api/complaints', $this->payload())
            ->assertStatus(201);

        $this->assertSame(1, Complaint::count());

        $this->getJson('/api/complaints')->assertOk();
    }

    public function test_description_can_be_updated(): void
    {
        ComplaintCategory::create(['category_code' => 'K3', 'category_name' => 'Kesehatan & Keselamatan Kerja']);
        $this->postJson('/api/complaints', $this->payload())->assertCreated();
        $complaint = Complaint::firstOrFail();

        $this->putJson("/api/complaints/{$complaint->id}", [
            'description' => 'Perusahaan sudah membayar seluruh upah pekerja.',
        ])->assertOk();

        $this->assertDatabaseHas('complaints', [
            'id' => $complaint->id,
            'description' => 'Perusahaan sudah membayar seluruh upah pekerja.',
        ]);
    }

    public function test_complaint_update_changes_related_complainant_and_company_data(): void
    {
        ComplaintCategory::create(['category_code' => 'K3', 'category_name' => 'Kesehatan & Keselamatan Kerja']);
        $this->postJson('/api/complaints', $this->payload())->assertCreated();
        $complaint = Complaint::firstOrFail();

        $response = $this->putJson("/api/complaints/{$complaint->id}", [
            'description' => 'Perusahaan sudah memperbaiki seluruh kendala pelaporan WLKP.',
            'complainant' => [
                'nama_lengkap' => 'Siti Aminah',
                'nik' => '3201010101010002',
                'alamat' => 'Jl. Baru No. 2',
                'jenis_kelamin' => 'perempuan',
                'jabatan' => 'Supervisor',
                'no_telp' => '081234567891',
                'email' => 'siti@example.com',
            ],
            'company' => [
                'nama_perusahaan' => 'PT Sejahtera',
                'sector_id' => 2,
                'alamat' => 'Jl. Industri No. 2',
                'jumlah_naker' => 25,
                'provinsi' => 'Jawa Barat',
                'kota_kab' => 'Bekasi',
                'kecamatan' => 'Cikarang',
                'kelurahan' => 'Waluya',
                'no_telp' => '0217654321',
                'email' => 'sejahtera@example.com',
            ],
        ]);

        $response->assertOk()
            ->assertJsonPath('data.complainant.nama_lengkap', 'Siti Aminah')
            ->assertJsonPath('data.company.nama_perusahaan', 'PT Sejahtera')
            ->assertJsonPath('data.description', 'Perusahaan sudah memperbaiki seluruh kendala pelaporan WLKP.');
        $this->assertDatabaseHas('complainants', [
            'id' => $complaint->complainant_id,
            'nama_lengkap' => 'Siti Aminah',
            'nik' => '3201010101010002',
        ]);
        $this->assertDatabaseHas('companies', [
            'id' => $complaint->company_id,
            'nama_perusahaan' => 'PT Sejahtera',
            'sector_id' => 2,
            'jumlah_naker' => 25,
            'size_id' => CompanySize::where('size_code', 'MEDIUM')->value('id'),
        ]);
        $this->assertDatabaseHas('complaints', [
            'id' => $complaint->id,
            'description' => 'Perusahaan sudah memperbaiki seluruh kendala pelaporan WLKP.',
        ]);
    }

    public function test_deleting_complaint_sets_is_deleted_and_hides_it(): void
    {
        ComplaintCategory::create(['category_code' => 'K3', 'category_name' => 'Kesehatan & Keselamatan Kerja']);
        $this->postJson('/api/complaints', $this->payload())->assertCreated();
        $complaint = Complaint::firstOrFail();

        $this->deleteJson("/api/complaints/{$complaint->id}")
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('complaints', [
            'id' => $complaint->id,
            'is_deleted' => true,
        ]);
        $this->assertSame(0, Complaint::count());
        $this->assertSame(1, Complaint::withoutGlobalScope('active')->count());
    }
}
