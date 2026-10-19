<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreComplaintRequest;
use App\Http\Requests\UpdateComplaintRequest;
use App\Http\Resources\ComplaintResource;
use App\Models\Company;
use App\Models\CompanySize;
use App\Models\Complainant;
use App\Models\Complaint;
use App\Models\ComplaintCategory;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ComplaintController extends Controller
{
    public function summary(Request $request)
    {
        $period = $request->validate([
            'period' => 'nullable|in:today',
        ])['period'] ?? null;

        $categories = ComplaintCategory::withCount([
            'complaints' => fn($query) => $query->when(
                $period === 'today',
                fn($query) => $query->whereDate('complaint_date', today()),
            ),
        ])
            ->orderBy('id')
            ->get(['id', 'category_code', 'category_name'])
            ->map(fn(ComplaintCategory $category) => [
                'id' => $category->id,
                'category_code' => $category->category_code,
                'category_name' => $category->category_name,
                'count' => $category->complaints_count,
            ]);

        $complaints = Complaint::query()->when(
            $period === 'today',
            fn($query) => $query->whereDate('complaint_date', today()),
        );

        return response()->json([
            'data' => $categories,
            'meta' => [
                'period' => $period ?? 'all',
                'total' => (clone $complaints)->count(),
                'uncategorized_count' => (clone $complaints)->whereNull('category_id')->count(),
            ],
        ]);
    }

    public function provinceSummary(Request $request)
    {
        $filters = $request->validate([
            'period' => 'nullable|in:today',
            'search' => 'nullable|string|max:255',
            'sort' => 'nullable|in:provinsi,total,category',
            'direction' => 'nullable|in:asc,desc',
            'category_id' => 'nullable|required_if:sort,category|integer|exists:complaint_categories,id',
            'with_categories' => 'nullable|boolean',
            'limit' => 'nullable|integer|between:1,100',
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|between:1,100',
        ]);

        $period = $filters['period'] ?? null;
        $withCategories = $request->boolean('with_categories');
        $sort = $filters['sort'] ?? 'total';
        $direction = $filters['direction'] ?? 'desc';
        $categoryIds = $withCategories ? ComplaintCategory::query()->orderBy('id')->pluck('id') : collect();

        if ($sort === 'category') {
            $categoryIds = $categoryIds->push($filters['category_id'])->unique()->values();
        }

        $baseQuery = Complaint::query()
            ->join('companies', 'companies.id', '=', 'complaints.company_id')
            ->when($period === 'today', fn($query) => $query->whereDate('complaint_date', today()))
            ->when($filters['search'] ?? null, function ($query, $search) {
                $query->whereRaw('LOWER(companies.provinsi) LIKE ?', ['%' . mb_strtolower($search) . '%']);
            });

        $totalComplaints = (clone $baseQuery)->count('complaints.id');
        $provinceQuery = (clone $baseQuery)
            ->select('companies.provinsi')
            ->selectRaw('COUNT(complaints.id) AS count');

        $categoryAliases = [];
        foreach ($categoryIds as $categoryId) {
            $categoryAliases[$categoryId] = 'category_' . $categoryId;
        }

        if ($categoryAliases !== []) {
            $provinceQuery->selectRaw(
                collect($categoryAliases)
                    ->map(fn(string $alias) => 'SUM(CASE WHEN complaints.category_id = ? THEN 1 ELSE 0 END) AS ' . $alias)
                    ->implode(', '),
                array_keys($categoryAliases)
            );
        }

        $provinceQuery->groupBy('companies.provinsi');

        $totalRows = (clone $baseQuery)->distinct()->count('companies.provinsi');

        if ($sort === 'provinsi') {
            $provinceQuery->orderBy('companies.provinsi', $direction);
        } elseif ($sort === 'category') {
            $provinceQuery->orderBy($categoryAliases[$filters['category_id']], $direction);
        } else {
            $provinceQuery->orderBy('count', $direction);
        }
        $provinceQuery->orderBy('companies.provinsi');

        $paginationRequested = array_key_exists('page', $filters) || array_key_exists('per_page', $filters);
        $limit = isset($filters['limit']) ? (int) $filters['limit'] : null;
        $page = null;
        $perPage = null;

        if ($limit !== null) {
            $rows = $provinceQuery->limit($limit)->get();
        } elseif ($paginationRequested) {
            $perPage = isset($filters['per_page']) ? (int) $filters['per_page'] : 15;
            $paginator = $provinceQuery->paginate($perPage)->withQueryString();
            $rows = $paginator->getCollection();
            $page = $paginator->currentPage();
        } else {
            $rows = $provinceQuery->get();
        }

        $categories = $withCategories
            ? ComplaintCategory::query()->orderBy('id')->get(['id', 'category_code', 'category_name'])
            : collect();

        $data = $rows->map(function ($province) use ($withCategories, $categories, $categoryAliases) {
            $row = [
                'provinsi' => $province->provinsi,
                'count' => (int) $province->count,
            ];

            if ($withCategories) {
                $row['category_counts'] = $categories->mapWithKeys(fn(ComplaintCategory $category) => [
                    $category->category_code => (int) $province->{$categoryAliases[$category->id]},
                ]);
            }

            return $row;
        })->values();

        return response()->json([
            'data' => $data,
            'categories' => $withCategories ? $categories->map(fn(ComplaintCategory $category) => [
                'id' => $category->id,
                'code' => $category->category_code,
                'name' => $category->category_name,
            ])->values() : null,
            'meta' => [
                'period' => $period ?? 'all',
                'total' => $totalComplaints,
                'total_complaints' => $totalComplaints,
                'total_rows' => $totalRows,
                'returned_rows' => $data->count(),
                'search' => $filters['search'] ?? null,
                'sort' => $sort,
                'direction' => $direction,
                'limit' => $limit,
                'page' => $page,
                'per_page' => $perPage,
                'last_page' => $perPage === null ? null : (int) ceil($totalRows / $perPage),
            ],
        ]);
    }

    private function buildProvinceSummaryData(Request $request): array
    {
        $filters = $request->validate([
            'period' => 'nullable|in:today',
            'search' => 'nullable|string|max:255',
            'sort' => 'nullable|in:provinsi,total,category',
            'direction' => 'nullable|in:asc,desc',
            'category_id' => 'nullable|required_if:sort,category|integer|exists:complaint_categories,id',
            'with_categories' => 'nullable|boolean',
            'limit' => 'nullable|integer|between:1,100',
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|between:1,100',
        ]);

        $period = $filters['period'] ?? null;
        $withCategories = filter_var($filters['with_categories'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $sort = $filters['sort'] ?? 'total';
        $direction = $filters['direction'] ?? 'desc';

        $categoryIds = $withCategories
            ? ComplaintCategory::query()->orderBy('id')->pluck('id')
            : collect();

        if ($sort === 'category' && ! empty($filters['category_id'])) {
            $categoryIds = $categoryIds->push($filters['category_id'])->unique()->values();
        }

        $baseQuery = Complaint::query()
            ->join('companies', 'companies.id', '=', 'complaints.company_id')
            ->when($period === 'today', fn($query) => $query->whereDate('complaint_date', today()))
            ->when($filters['search'] ?? null, function ($query, $search) {
                $query->whereRaw('LOWER(companies.provinsi) LIKE ?', ['%' . mb_strtolower($search) . '%']);
            });

        $totalComplaints = (clone $baseQuery)->count('complaints.id');
        $provinceQuery = (clone $baseQuery)
            ->select('companies.provinsi')
            ->selectRaw('COUNT(complaints.id) AS count');

        $categoryAliases = [];
        foreach ($categoryIds as $categoryId) {
            $categoryAliases[$categoryId] = 'category_' . $categoryId;
        }

        if ($categoryAliases !== []) {
            $provinceQuery->selectRaw(
                collect($categoryAliases)
                    ->map(fn(string $alias) => 'SUM(CASE WHEN complaints.category_id = ? THEN 1 ELSE 0 END) AS ' . $alias)
                    ->implode(', '),
                array_keys($categoryAliases)
            );
        }

        $provinceQuery->groupBy('companies.provinsi');

        $totalRows = (clone $baseQuery)->distinct()->count('companies.provinsi');

        if ($sort === 'provinsi') {
            $provinceQuery->orderBy('companies.provinsi', $direction);
        } elseif ($sort === 'category' && ! empty($filters['category_id']) && isset($categoryAliases[$filters['category_id']])) {
            $provinceQuery->orderBy($categoryAliases[$filters['category_id']], $direction);
        } else {
            $provinceQuery->orderBy('count', $direction);
        }
        $provinceQuery->orderBy('companies.provinsi');

        $limit = isset($filters['limit']) ? (int) $filters['limit'] : null;
        $rows = $limit !== null
            ? $provinceQuery->limit($limit)->get()
            : $provinceQuery->get();

        $categories = $withCategories
            ? ComplaintCategory::query()->orderBy('id')->get(['id', 'category_code', 'category_name'])
            : collect();

        $data = $rows->map(function ($province) use ($withCategories, $categories, $categoryAliases) {
            $row = [
                'provinsi' => $province->provinsi,
                'count' => (int) $province->count,
            ];

            if ($withCategories) {
                $row['category_counts'] = $categories->mapWithKeys(fn(ComplaintCategory $category) => [
                    $category->category_code => (int) $province->{$categoryAliases[$category->id]},
                ]);
            }

            return $row;
        })->values();

        return compact('data', 'categories', 'totalComplaints', 'totalRows', 'period', 'withCategories', 'filters', 'categoryAliases');
    }

    public function provinceSummaryPdf(Request $request)
    {
        $data = $this->buildProvinceSummaryData($request);

        $headers = [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="laporan-provinsi-' . now()->format('Ymd_His') . '.pdf"; filename*=UTF-8\'\'laporan-provinsi-' . now()->format('Ymd_His') . '.pdf',
        ];

        return response()->stream(function () use ($data) {
            $options = new Options;
            $options->set('isRemoteEnabled', false);
            $options->set('defaultFont', 'DejaVu Sans');

            $dompdf = new Dompdf($options);
            $dompdf->loadHtml($this->buildProvinceSummaryPdfHtml($data), 'UTF-8');
            $dompdf->setPaper('A4', 'landscape');
            $dompdf->render();

            echo $dompdf->output();
        }, 200, $headers);
    }

    private function buildProvinceSummaryPdfHtml(array $data): string
    {
        $period = $data['period'] === 'today' ? 'Hari Ini (' . today()->format('d M Y') . ')' : 'Semua Waktu';
        $printedAt = now()->format('d M Y H:i');
        $total = $data['totalComplaints'];
        $totalRows = $data['totalRows'];
        $withCats = $data['withCategories'];
        $categories = $data['categories'];
        $categoryAliases = $data['categoryAliases'];

        $headerCols = '<th>No</th><th>Provinsi</th><th style="text-align:right">Total</th>';
        if ($withCats) {
            foreach ($categories as $cat) {
                $headerCols .= '<th style="text-align:center">' . $cat->category_code . '</th>';
            }
        }
        $headerCols .= '</tr>';

        $bodyRows = '';
        $no = 1;
        foreach ($data['data'] as $row) {
            $cells = '<td style="text-align:center">' . $no++ . '</td><td>' . $row['provinsi'] . '</td><td style="text-align:right">' . $row['count'] . '</td>';
            if ($withCats) {
                foreach ($categories as $cat) {
                    $cnt = $row['category_counts'][$cat->category_code] ?? 0;
                    $cells .= '<td style="text-align:center">' . $cnt . '</td>';
                }
            }
            $bodyRows .= '<tr>' . $cells . '</tr>';
        }

        return <<<HTML
<!doctype html>
<html><head><meta charset="UTF-8"><style>
body { font-family: DejaVu Sans, sans-serif; font-size: 9pt; color: #111; line-height: 1.3; }
h1 { font-size: 16pt; margin: 0 0 4px; text-align:center; }
.meta { text-align:center; font-size: 8pt; color:#555; margin-bottom: 12px; }
table { width: 100%; border-collapse: collapse; font-size: 8pt; }
th, td { border: 1px solid #ddd; padding: 4px 5px; }
th { background: #f0f0f0; font-weight: bold; }
tr:nth-child(even) td { background: #fafafa; }
@page { margin: 10mm; size: A4 landscape; }
</style></head><body>

<h1>Rekap Pengaduan per Provinsi</h1>
<div class="meta">Periode: {$period} | Dicetak: {$printedAt} | Total Pengaduan: {$total} | Provinsi: {$totalRows}</div>

<table>
    <thead><tr>{$headerCols}</thead>
    <tbody>{$bodyRows}</tbody>
</table>

</body></html>
HTML;
    }

    public function statistics(Request $request)
    {
        $period = $request->validate([
            'period' => 'nullable|in:today',
        ])['period'] ?? null;
        $today = fn($query) => $query->when(
            $period === 'today',
            fn($query) => $query->whereDate('complaint_date', today()),
        );

        $complaints = Complaint::query();
        $today($complaints);

        $categories = ComplaintCategory::withCount(['complaints' => $today])
            ->orderBy('id')
            ->get(['id', 'category_code', 'category_name'])
            ->map(fn(ComplaintCategory $category) => [
                'id' => $category->id,
                'category_code' => $category->category_code,
                'category_name' => $category->category_name,
                'count' => $category->complaints_count,
            ])
            ->values();

        $companySizes = CompanySize::orderBy('min_employees')
            ->get(['id', 'size_code', 'size_name', 'min_employees', 'max_employees'])
            ->map(function (CompanySize $size) use ($period) {
                $count = Complaint::query()
                    ->when($period === 'today', fn($query) => $query->whereDate('complaint_date', today()))
                    ->whereHas('company', fn($query) => $query->where('size_id', $size->id))
                    ->count();

                return [
                    'size_code' => $size->size_code,
                    'size_name' => $size->size_name,
                    'count' => $count,
                ];
            })
            ->values();

        return response()->json([
            'data' => [
                'total_complaints' => (clone $complaints)->count(),
                'by_company_size' => $companySizes,
                'by_category' => $categories,
            ],
            'meta' => [
                'period' => $period ?? 'all',
                'uncategorized_count' => (clone $complaints)->whereNull('category_id')->count(),
            ],
        ]);
    }

    public function index(Request $request)
    {
        $filters = $request->validate([
            'search' => 'nullable|string|max:255',
            'category_id' => 'nullable|integer|exists:complaint_categories,id',
            'month' => 'nullable|integer|between:1,12',
            'year' => 'nullable|integer|between:1900,2200',
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|between:1,100',
        ]);

        $complaints = Complaint::with(['complainant', 'company.sector', 'company.size', 'category', 'attachments'])
            ->when($filters['search'] ?? null, function ($query, $search) {
                $term = '%' . mb_strtolower($search) . '%';

                $query->where(function ($query) use ($term) {
                    $query->whereRaw('LOWER(ticket_number) LIKE ?', [$term])
                        ->orWhereRaw('LOWER(description) LIKE ?', [$term])
                        ->orWhereHas('complainant', fn($query) => $query
                            ->whereRaw('LOWER(nama_lengkap) LIKE ?', [$term])
                            ->orWhereRaw('LOWER(nik) LIKE ?', [$term]))
                        ->orWhereHas('company', fn($query) => $query
                            ->whereRaw('LOWER(nama_perusahaan) LIKE ?', [$term]))
                        ->orWhereHas('category', fn($query) => $query
                            ->whereRaw('LOWER(category_name) LIKE ?', [$term]));
                });
            })
            ->when($filters['category_id'] ?? null, fn($query, $categoryId) => $query->byCategory($categoryId))
            ->when($filters['month'] ?? null, fn($query, $month) => $query->whereMonth('complaint_date', $month))
            ->when($filters['year'] ?? null, fn($query, $year) => $query->whereYear('complaint_date', $year))
            ->latest('complaint_date')
            ->paginate($filters['per_page'] ?? 15)
            ->withQueryString();

        return ComplaintResource::collection($complaints);
    }

    public function store(StoreComplaintRequest $request)
    {
        $complaint = DB::transaction(function () use ($request) {
            $validated = $request->validated();

            $complainant = Complainant::firstOrCreate(
                ['nik' => $validated['complainant']['nik']],
                $validated['complainant']
            );

            $companySize = $this->companySizeFor($validated['company']['jumlah_naker']);

            $company = Company::updateOrCreate(
                [
                    'nama_perusahaan' => $validated['company']['nama_perusahaan'],
                    'kota_kab' => $validated['company']['kota_kab'],
                ],
                [...$validated['company'], 'size_id' => $companySize->id]
            );

            $complaint = $this->createComplaintWithUniqueTicket([
                'complainant_id' => $complainant->id,
                'company_id' => $company->id,
                'category_id' => $validated['category_id'],
                'description' => $validated['description'],
                'complaint_date' => $validated['complaint_date'] ?? now(),
            ]);

            foreach ($request->file('attachments', []) as $file) {
                $complaint->addAttachment($file);
            }

            return $complaint;
        });

        return (new ComplaintResource($complaint->load(['complainant', 'company.sector', 'company.size', 'category', 'attachments'])))
            ->additional(['success' => true, 'message' => 'Aduan berhasil dibuat'])
            ->response()
            ->setStatusCode(201);
    }

    public function show(Complaint $complaint)
    {
        return new ComplaintResource($complaint->load(['complainant', 'company.sector', 'company.size', 'category', 'attachments']));
    }

    public function pdf(Complaint $complaint)
    {
        $complaint->load(['complainant', 'company.sector', 'company.size', 'category', 'attachments']);

        $field = fn($value) => htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');

        // ALL-CAPS location values ("KALIMANTAN TIMUR") -> "Kalimantan Timur".
        $titleCase = function ($value) {
            $value = (string) $value;

            return $value !== '' && $value === mb_strtoupper($value)
                ? ucwords(mb_strtolower($value))
                : $value;
        };

        $company = $complaint->company;
        $complainant = $complaint->complainant;

        // Kolom di DB adalah "file_path" dan sudah berupa path relatif bersih
        // (contoh: "complaints/11/xxx.jpg"), tapi tetap di-strip jaga-jaga
        // kalau ada data lama yang masih tersimpan sebagai "/storage/xxx".
        $diskPath = fn($value) => ltrim(str_replace('/storage/', '', (string) $value), '/');

        // "Foto Pemohon" = verification selfie (file_type = photo).
        $photoAttachment = $complaint->attachments->firstWhere('file_type', 'photo');
        $photoHtml = '<div class="photo-placeholder"></div>';
        if ($photoAttachment) {
            $path = $diskPath($photoAttachment->file_path);
            try {
                if (Storage::disk('public')->exists($path)) {
                    $photoHtml = '<img class="photo" src="data:' . $field($photoAttachment->mime_type)
                        . ';base64,' . base64_encode(Storage::disk('public')->get($path))
                        . '" alt="Foto Pemohon">';
                }
            } catch (\Exception $e) {
                \Log::warning("Gagal membaca foto pemohon untuk aduan #{$complaint->id}: {$e->getMessage()}");
            }
        }

        $nomor = $field($complaint->ticket_number);
        $tanggalSurat = $field(optional($complaint->complaint_date ?? now())->translatedFormat('d F Y'));
        $kotaSurat = 'Jakarta';
        $sektorIndustri = $company?->sector?->sector_name ?? $company?->sektor_industri;
        $perihal = $complaint->category?->category_name ?? '-';

        // Borderless two-column "field / value" row.
        $row = fn(string $label, $value) => '<tr><td class="field">' . $field($label) . '</td>'
            . '<td class="value">' . $field($value) . '</td></tr>';

        $html = '<!doctype html><html><head><meta charset="UTF-8"><style>
    @page { margin: 90px 70px; }
    body { font-family: "Times New Roman", serif; font-size: 12pt; color: #000; line-height: 1.4; }
    table { border-collapse: collapse; }
    table.header-table { width: 100%; margin-bottom: 24px; }
    table.header-table td { padding: 1px 0; vertical-align: top; }
    table.header-table td.label { width: 90px; }
    table.header-table td.sep { width: 14px; }
    .recipient { margin-bottom: 18px; }
    .recipient .bold { font-weight: bold; }
    .opening { margin-bottom: 16px; }
    .body-paragraph { text-align: justify; text-indent: 40px; margin-bottom: 18px; }
    h3.section-title { font-size: 12pt; font-weight: bold; margin: 14px 0 8px; }
    table.data-table { width: 100%; margin-bottom: 10px; }
    table.data-table td { padding: 7px 0; vertical-align: top; }
    table.data-table td.field { width: 45%; }
    table.data-table td.value { width: 55%; }
    .kendala-text { margin-bottom: 20px; white-space: pre-wrap; }
    .closing { text-align: justify; margin-bottom: 30px; }
    table.footer-table { width: 100%; margin-top: 10px; }
    table.footer-table td { vertical-align: top; }
    td.footer-photo { width: 45%; text-align: center; }
    td.footer-photo .caption { margin-top: 6px; }
    td.footer-sign { width: 55%; text-align: center; }
    .footer-sign .place-date { margin-bottom: 6px; }
    .footer-sign .regards { margin-bottom: 60px; }
    .photo { width: 170px; height: 130px; object-fit: cover; border: 1px solid #ccc; }
    .photo-placeholder { width: 170px; height: 130px; border: 1px solid #ccc; margin: 0 auto; }
</style></head><body>

    <table class="header-table">
        <tr><td class="label">Nomor</td><td class="sep">:</td><td>' . $nomor . '</td></tr>
        <tr><td class="label">Sifat</td><td class="sep">:</td><td>Segera</td></tr>
        <tr><td class="label">Lampiran</td><td class="sep">:</td><td>-</td></tr>
        <tr><td class="label">Perihal</td><td class="sep">:</td><td>' . $perihal . '</td></tr>
    </table>

    <div class="recipient">
        <div class="bold">Kepada Yth.</div>
        <div>Direktur Bina Sistem Pengawasan Ketenagakerjaan</div>
        <div>di Jakarta</div>
    </div>

    <div class="opening">Dengan hormat,</div>

    <div class="body-paragraph">
        Kami yang bertanda tangan di bawah ini, selaku perwakilan ' . $field($company?->nama_perusahaan ?: 'perusahaan') . '
        bermaksud menyampaikan permohonan tindak lanjut kendala pelaporan WLKP,
        dengan rincian data sebagai berikut:
    </div>

    <h3 class="section-title">Data Pemohon:</h3>
    <table class="data-table">
        ' . $row('Nama Pemohon', $complainant?->nama_lengkap) . '
        ' . $row('NIK', $complainant?->nik) . '
        ' . $row('Alamat', $complainant?->alamat) . '
        ' . $row('Jenis Kelamin', $complainant?->jenis_kelamin) . '
        ' . $row('Jabatan', $complainant?->jabatan) . '
        ' . $row('Telepon', $complainant?->no_telp) . '
        ' . $row('Email', $complainant?->email) . '
    </table>

    <h3 class="section-title">Data Perusahaan:</h3>
    <table class="data-table">
        ' . $row('Nama Perusahaan', $company?->nama_perusahaan) . '
        ' . $row('Sektor Industri', $sektorIndustri) . '
        ' . $row('Alamat Perusahaan', $company?->alamat) . '
        ' . $row('Jumlah Tenaga Kerja', $company?->jumlah_naker) . '
        ' . $row('Provinsi', $titleCase($company?->provinsi)) . '
        ' . $row('Kabupaten/Kota', $titleCase($company?->kota_kab)) . '
        ' . $row('Kecamatan', $titleCase($company?->kecamatan)) . '
        ' . $row('Kelurahan', $titleCase($company?->kelurahan)) . '
        ' . $row('Telepon Perusahaan', $company?->no_telp) . '
        ' . $row('Email Perusahaan', $company?->email) . '
    </table>

    <h3 class="section-title">Kendala Pelaporan:</h3>
    <div class="kendala-text">' . $field($complaint->description) . '</div>

    <div class="closing">
        Demikian surat ini kami sampaikan. Atas perhatian dan tindak lanjutnya,
        kami sampaikan ucapan terima kasih.
    </div>

    <table class="footer-table">
        <tr>
            <td class="footer-photo">
                ' . $photoHtml . '
                <div class="caption">Foto Pemohon</div>
            </td>
            <td class="footer-sign">
                <div class="place-date">' . $kotaSurat . ', ' . $tanggalSurat . '</div>
                <div class="regards">Hormat kami,</div>
                <div class="name">' . $field($complainant?->nama_lengkap) . '</div>
                <div>No. Hp: ' . $field($complainant?->no_telp) . '</div>
            </td>
        </tr>
    </table>

</body></html>';

        $dompdf = new Dompdf((new Options)->setIsRemoteEnabled(false));
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper('A4');
        $dompdf->render();

        return response($dompdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="laporan-pengaduan-' . $complaint->id . '.pdf"; filename*=UTF-8\'\'laporan-pengaduan-' . $complaint->id . '.pdf',
        ]);
    }

    public function update(UpdateComplaintRequest $request, Complaint $complaint)
    {
        DB::transaction(function () use ($request, $complaint) {
            $validated = $request->validated();

            $complaint->update(array_intersect_key($validated, array_flip(['category_id', 'description'])));

            if (($complainantData = $validated['complainant'] ?? null) && $complaint->complainant) {
                $complaint->complainant->update($complainantData);
            }

            if (($companyData = $validated['company'] ?? null) && $complaint->company) {
                if (isset($companyData['jumlah_naker'])) {
                    $companyData['size_id'] = $this->companySizeFor($companyData['jumlah_naker'])->id;
                }

                $complaint->company->update($companyData);
            }
        });

        return new ComplaintResource($complaint->fresh()->load(['complainant', 'company.sector', 'company.size', 'category', 'attachments']));
    }

    public function destroy(Complaint $complaint)
    {
        $complaint->update(['is_deleted' => true]);

        return response()->json(['success' => true, 'message' => 'Aduan dihapus']);
    }

    private function companySizeFor(int $employeeCount): CompanySize
    {
        return CompanySize::query()
            ->where('min_employees', '<=', $employeeCount)
            ->where(fn($query) => $query
                ->whereNull('max_employees')
                ->orWhere('max_employees', '>=', $employeeCount))
            ->firstOrFail();
    }

    private function createComplaintWithUniqueTicket(array $data, int $retries = 10): Complaint
    {
        $base = Complaint::withoutGlobalScopes()->whereDate('created_at', today())->count();

        for ($attempt = 1; $attempt <= $retries; $attempt++) {
            try {
                return Complaint::create([
                    ...$data,
                    'ticket_number' => 'TKT-' . now()->format('d-m-Y') . '-' . str_pad((string) ($base + $attempt), 3, '0', STR_PAD_LEFT),
                ]);
            } catch (QueryException $e) {
                // Nomor sudah dipakai aduan lain, coba urutan berikutnya.
            }
        }

        throw new \RuntimeException('Gagal membuat nomor tiket aduan yang unik');
    }
}
