<?php

namespace App\Console\Commands;

use App\Models\Company;
use App\Models\CompanySize;
use App\Models\Complainant;
use App\Models\Complaint;
use App\Models\ComplaintCategory;
use App\Models\IndustrialSector;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use SplFileObject;

class ImportComplaintsCsv extends Command
{
    /**
     * @var string
     */
    protected $signature = 'complaints:import-csv
        {path : Path ke file CSV hasil export Google Form}
        {--limit= : Hanya proses N baris data pertama}
        {--dry-run : Validasi dan tampilkan hasil tanpa menyimpan ke database}';

    /**
     * @var string
     */
    protected $description = 'Impor data pengaduan dari CSV Google Form ke tabel complainants, companies, dan complaints';

    private const COLUMN_COUNT = 20;

    private const UNSPECIFIED_SECTOR_CODE = 'LEGACY_UNSPECIFIED';

    /**
     * Alias "Jenis Pengaduan" di Google Form ke category_code.
     *
     * @var array<string, string>
     */
    private const CATEGORY_ALIASES = [
        'wajib lapor ketenagakerjaan perusahaan (wlkp)' => 'WAJIB_LAPOR',
        'waktu kerja waktu istirahat' => 'WAKTU_KERJA',
    ];

    public function handle(): int
    {
        $path = (string) $this->argument('path');

        if (! is_file($path) || ! is_readable($path)) {
            $this->error("File tidak ditemukan atau tidak bisa dibaca: {$path}");

            return self::FAILURE;
        }

        if (CompanySize::query()->doesntExist()) {
            $this->error('Tabel company_sizes masih kosong. Jalankan: php artisan db:seed --class=CompanySizeSeeder');

            return self::FAILURE;
        }

        $limit = $this->option('limit') !== null ? max(0, (int) $this->option('limit')) : null;
        $isDryRun = (bool) $this->option('dry-run');

        $summary = ['imported' => 0, 'duplicate' => 0, 'skipped' => 0];

        foreach ($this->readRows($path, $limit) as $lineNumber => $columns) {
            try {
                $row = $this->normalizeRow($columns);
            } catch (RuntimeException $exception) {
                $summary['skipped']++;
                $this->warn("Baris {$lineNumber} dilewati: {$exception->getMessage()}");

                continue;
            }

            if ($isDryRun) {
                $summary['imported']++;
                $this->line("Baris {$lineNumber} valid: {$row['complainant']['nama_lengkap']} / {$row['company']['nama_perusahaan']}");

                continue;
            }

            $wasCreated = DB::transaction(fn (): bool => $this->storeRow($row));

            $wasCreated ? $summary['imported']++ : $summary['duplicate']++;
        }

        $this->table(
            [$isDryRun ? 'Valid (dry run)' : 'Diimpor', 'Duplikat', 'Dilewati'],
            [[$summary['imported'], $summary['duplicate'], $summary['skipped']]],
        );

        return self::SUCCESS;
    }

    /**
     * Baca baris data CSV (tanpa header), dengan nomor baris file sebagai key.
     *
     * @return iterable<int, array<int, string>>
     */
    private function readRows(string $path, ?int $limit): iterable
    {
        $file = new SplFileObject($path);
        $file->setFlags(SplFileObject::READ_CSV | SplFileObject::SKIP_EMPTY | SplFileObject::READ_AHEAD);

        $processed = 0;

        foreach ($file as $index => $columns) {
            if ($index === 0 || $columns === [null]) {
                continue;
            }

            if ($limit !== null && $processed >= $limit) {
                break;
            }

            $processed++;

            yield $index + 1 => array_map(fn ($value): string => trim((string) $value), $columns);
        }
    }

    /**
     * @param  array<int, string>  $columns
     * @return array{complaint_date: CarbonImmutable, category_label: string, description: string, complainant: array<string, string>, company: array<string, string|int>, sector_label: string}
     */
    private function normalizeRow(array $columns): array
    {
        if (count($columns) !== self::COLUMN_COUNT) {
            throw new RuntimeException('jumlah kolom '.count($columns).', seharusnya '.self::COLUMN_COUNT);
        }

        [
            $timestamp, $categoryLabel, $name, $nik, $address, $gender, $position, $phone, $email,
            $companyName, $sectorLabel, $companyAddress, $employeeCount, $province, $city,
            $district, $village, $companyPhone, $companyEmail, $description,
        ] = $columns;

        if (! preg_match('/^\d{16}$/', $nik)) {
            throw new RuntimeException("NIK tidak valid ({$nik})");
        }

        foreach (['No. Telpon' => $phone, 'No. Telp Perusahaan' => $companyPhone] as $label => $number) {
            if (mb_strlen($number) > 20) {
                throw new RuntimeException("{$label} lebih dari 20 karakter ({$number})");
            }
        }

        try {
            $complaintDate = CarbonImmutable::createFromFormat('d/m/Y G:i:s', $timestamp, config('app.timezone'));
        } catch (\Throwable) {
            throw new RuntimeException("format Timestamp tidak dikenali ({$timestamp})");
        }

        return [
            'complaint_date' => $complaintDate,
            'category_label' => $categoryLabel,
            'description' => $description,
            'sector_label' => $sectorLabel,
            'complainant' => [
                'nama_lengkap' => $name,
                'nik' => $nik,
                'alamat' => $address,
                'jenis_kelamin' => $this->normalizeGender($gender),
                'jabatan' => $position,
                'no_telp' => $phone,
                'email' => $email,
            ],
            'company' => [
                'nama_perusahaan' => $companyName,
                'alamat' => $companyAddress,
                'jumlah_naker' => $this->parseEmployeeCount($employeeCount),
                'provinsi' => $province,
                'kota_kab' => $city,
                'kecamatan' => $district,
                'kelurahan' => $village,
                'no_telp' => $companyPhone,
                'email' => $companyEmail,
            ],
        ];
    }

    /**
     * Simpan satu baris. Mengembalikan false jika aduan yang sama sudah pernah diimpor.
     *
     * @param  array{complaint_date: CarbonImmutable, category_label: string, description: string, complainant: array<string, string>, company: array<string, string|int>, sector_label: string}  $row
     */
    private function storeRow(array $row): bool
    {
        $complainant = Complainant::firstOrCreate(['nik' => $row['complainant']['nik']], $row['complainant']);

        $company = Company::updateOrCreate(
            [
                'nama_perusahaan' => $row['company']['nama_perusahaan'],
                'kota_kab' => $row['company']['kota_kab'],
            ],
            [
                ...$row['company'],
                'sector_id' => $this->resolveSectorId($row['sector_label']),
                'size_id' => $this->companySizeFor((int) $row['company']['jumlah_naker'])->id,
            ],
        );

        $isAlreadyImported = Complaint::withoutGlobalScopes()
            ->where('complainant_id', $complainant->id)
            ->where('company_id', $company->id)
            ->where('complaint_date', $row['complaint_date'])
            ->exists();

        if ($isAlreadyImported) {
            return false;
        }

        $this->createComplaintWithUniqueTicket([
            'complainant_id' => $complainant->id,
            'company_id' => $company->id,
            'category_id' => $this->resolveCategoryId($row['category_label']),
            'description' => $row['description'],
            'complaint_date' => $row['complaint_date'],
        ], $row['complaint_date']);

        return true;
    }

    private function normalizeGender(string $gender): string
    {
        $value = strtolower($gender);

        return match (true) {
            preg_match('/^(l\b|laki|pria|male)/', $value) === 1 => 'laki-laki',
            preg_match('/^(p\b|perempuan|wanita|female)/', $value) === 1 => 'perempuan',
            default => throw new RuntimeException("Jenis Kelamin tidak dikenali ({$gender})"),
        };
    }

    /**
     * Ambil angka pertama dari isian bebas, mis. "60-70" -> 60, "300an" -> 300, "-" -> 0.
     */
    private function parseEmployeeCount(string $employeeCount): int
    {
        $digits = preg_replace('/[.,](?=\d{3}\b)/', '', $employeeCount);

        return preg_match('/\d+/', (string) $digits, $matches) === 1 ? (int) $matches[0] : 0;
    }

    private function companySizeFor(int $employeeCount): CompanySize
    {
        return CompanySize::query()
            ->where('min_employees', '<=', max(1, $employeeCount))
            ->where(fn ($query) => $query
                ->whereNull('max_employees')
                ->orWhere('max_employees', '>=', max(1, $employeeCount)))
            ->firstOrFail();
    }

    private function resolveSectorId(string $sectorLabel): int
    {
        $sector = IndustrialSector::query()
            ->whereRaw('LOWER(sector_name) = ?', [strtolower($sectorLabel)])
            ->orWhereRaw('LOWER(sector_code) = ?', [strtolower($sectorLabel)])
            ->first();

        return $sector?->id ?? IndustrialSector::firstOrCreate(
            ['sector_code' => self::UNSPECIFIED_SECTOR_CODE],
            ['sector_name' => 'Tidak ditentukan'],
        )->id;
    }

    private function resolveCategoryId(string $categoryLabel): ?int
    {
        $label = strtolower($categoryLabel);

        if (isset(self::CATEGORY_ALIASES[$label])) {
            return ComplaintCategory::query()->where('category_code', self::CATEGORY_ALIASES[$label])->value('id');
        }

        return ComplaintCategory::query()->whereRaw('LOWER(category_name) = ?', [$label])->value('id');
    }

    /**
     * Sama seperti ComplaintController, tetapi nomor tiket memakai tanggal aduan asli.
     *
     * @param  array<string, mixed>  $data
     */
    private function createComplaintWithUniqueTicket(array $data, CarbonImmutable $complaintDate, int $retries = 10): Complaint
    {
        $prefix = 'TKT-'.$complaintDate->format('d-m-Y').'-';
        $base = Complaint::withoutGlobalScopes()->where('ticket_number', 'like', $prefix.'%')->count();

        for ($attempt = 1; $attempt <= $retries; $attempt++) {
            try {
                return DB::transaction(fn (): Complaint => Complaint::create([
                    ...$data,
                    'ticket_number' => $prefix.str_pad((string) ($base + $attempt), 3, '0', STR_PAD_LEFT),
                ]));
            } catch (QueryException) {
                // Nomor sudah dipakai, coba urutan berikutnya.
            }
        }

        throw new RuntimeException('Gagal membuat nomor tiket aduan yang unik');
    }
}
