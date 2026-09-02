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
use App\Models\SurveyResponse;
use Dompdf\Dompdf;
use Dompdf\Options;
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
            'complaints' => fn ($query) => $query->when(
                $period === 'today',
                fn ($query) => $query->whereDate('complaint_date', today()),
            ),
        ])
            ->orderBy('id')
            ->get(['id', 'category_code', 'category_name'])
            ->map(fn (ComplaintCategory $category) => [
                'id' => $category->id,
                'category_code' => $category->category_code,
                'category_name' => $category->category_name,
                'count' => $category->complaints_count,
            ]);

        $complaints = Complaint::query()->when(
            $period === 'today',
            fn ($query) => $query->whereDate('complaint_date', today()),
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

    public function statistics(Request $request)
    {
        $period = $request->validate([
            'period' => 'nullable|in:today',
        ])['period'] ?? null;
        $today = fn ($query) => $query->when(
            $period === 'today',
            fn ($query) => $query->whereDate('complaint_date', today()),
        );

        $complaints = Complaint::query();
        $today($complaints);

        $categories = ComplaintCategory::withCount(['complaints' => $today])
            ->orderBy('id')
            ->get(['id', 'category_code', 'category_name'])
            ->map(fn (ComplaintCategory $category) => [
                'id' => $category->id,
                'category_code' => $category->category_code,
                'category_name' => $category->category_name,
                'count' => $category->complaints_count,
            ])
            ->values();

        $companySizes = CompanySize::orderBy('min_employees')
            ->get(['size_code', 'size_name', 'min_employees', 'max_employees'])
            ->map(function (CompanySize $size) use ($period) {
                $count = Complaint::query()
                    ->when($period === 'today', fn ($query) => $query->whereDate('complaint_date', today()))
                    ->whereHas('company', fn ($query) => $query
                        ->where('jumlah_naker', '>=', $size->min_employees)
                        ->when($size->max_employees !== null, fn ($query) => $query->where('jumlah_naker', '<=', $size->max_employees)))
                    ->count();

                return [
                    'size_code' => $size->size_code,
                    'size_name' => $size->size_name,
                    'count' => $count,
                ];
            })
            ->values();

        $answers = SurveyResponse::whereHas('survey.complaint', $today)
            ->pluck('answer')
            ->filter(fn ($answer) => is_numeric(trim((string) $answer)) && (float) $answer >= 1 && (float) $answer <= 5)
            ->map(fn ($answer) => (float) $answer);

        return response()->json([
            'data' => [
                'total_complaints' => (clone $complaints)->count(),
                'completed_complaints' => (clone $complaints)->where('status', 'SELESAI')->count(),
                'satisfaction_index' => $answers->isEmpty() ? null : round($answers->avg(), 2),
                'satisfaction_scale' => 5,
                'satisfaction_responses' => $answers->count(),
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
            'status' => 'nullable|in:DIPROSES,SELESAI',
            'category_id' => 'nullable|integer|exists:complaint_categories,id',
            'month' => 'nullable|integer|between:1,12',
            'year' => 'nullable|integer|between:1900,2200',
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|between:1,100',
        ]);

        $complaints = Complaint::with(['complainant', 'company.sector', 'category', 'assignedAdmin', 'attachments'])
            ->when($filters['search'] ?? null, function ($query, $search) {
                $term = '%'.mb_strtolower($search).'%';

                $query->where(function ($query) use ($term) {
                    $query->whereRaw('LOWER(ticket_number) LIKE ?', [$term])
                        ->orWhereRaw('LOWER(description) LIKE ?', [$term])
                        ->orWhereHas('complainant', fn ($query) => $query
                            ->whereRaw('LOWER(nama_lengkap) LIKE ?', [$term])
                            ->orWhereRaw('LOWER(nik) LIKE ?', [$term]))
                        ->orWhereHas('company', fn ($query) => $query
                            ->whereRaw('LOWER(nama_perusahaan) LIKE ?', [$term]))
                        ->orWhereHas('category', fn ($query) => $query
                            ->whereRaw('LOWER(category_name) LIKE ?', [$term]));
                });
            })
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->byStatus($status))
            ->when($filters['category_id'] ?? null, fn ($query, $categoryId) => $query->byCategory($categoryId))
            ->when($filters['month'] ?? null, fn ($query, $month) => $query->whereMonth('complaint_date', $month))
            ->when($filters['year'] ?? null, fn ($query, $year) => $query->whereYear('complaint_date', $year))
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

            $company = Company::firstOrCreate(
                [
                    'nama_perusahaan' => $validated['company']['nama_perusahaan'],
                    'kota_kab' => $validated['company']['kota_kab'],
                ],
                $validated['company']
            );

            $complaint = Complaint::create([
                'complainant_id' => $complainant->id,
                'company_id' => $company->id,
                'category_id' => $validated['category_id'],
                'ticket_number' => $this->nextTicketNumber(),
                'description' => $validated['description'],
                'priority' => 'MEDIUM',
                'follow_up_date' => $validated['follow_up_date'] ?? null,
                'complaint_date' => $validated['complaint_date'] ?? now(),
            ]);

            foreach ($request->file('attachments', []) as $file) {
                $complaint->addPhoto($file);
            }

            return $complaint;
        });

        return (new ComplaintResource($complaint->load(['complainant', 'company.sector', 'category', 'assignedAdmin', 'attachments'])))
            ->additional(['success' => true, 'message' => 'Aduan berhasil dibuat'])
            ->response()
            ->setStatusCode(201);
    }

    public function show(Complaint $complaint)
    {
        return new ComplaintResource($complaint->load(['complainant', 'company.sector', 'category', 'assignedAdmin', 'attachments', 'updates']));
    }

    public function pdf(Complaint $complaint)
    {
        $complaint->load(['complainant', 'company.sector', 'category', 'attachments']);

        $field = fn ($value) => htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');

        // ALL-CAPS location values ("KALIMANTAN TIMUR") -> "Kalimantan Timur".
        $titleCase = function ($value) {
            $value = (string) $value;

            return $value !== '' && $value === mb_strtoupper($value)
                ? ucwords(mb_strtolower($value))
                : $value;
        };

        $company = $complaint->company;
        $complainant = $complaint->complainant;

        // "/storage/xxx" -> "xxx" for the public disk.
        $diskPath = fn ($url) => ltrim(str_replace('/storage/', '', (string) $url), '/');

        // "Foto Pemohon" = verification selfie (file_type = photo).
        $photoAttachment = $complaint->attachments->firstWhere('file_type', 'photo');
        $photoHtml = '<div class="photo-placeholder"></div>';
        if ($photoAttachment) {
            $path = $diskPath($photoAttachment->url);
            if (Storage::disk('public')->exists($path)) {
                $photoHtml = '<img class="photo" src="data:'.$field($photoAttachment->mime_type)
                    .';base64,'.base64_encode(Storage::disk('public')->get($path))
                    .'" alt="Foto Pemohon">';
            }
        }

        $nomor = $field($complaint->id ?: $complaint->ticket_number);
        $tanggalSurat = $field(optional($complaint->complaint_date ?? now())->translatedFormat('d F Y'));
        $kotaSurat = 'Jakarta';
        $sektorIndustri = $company?->sector?->sector_name ?? $company?->sektor_industri;

        // Borderless two-column "field / value" row.
        $row = fn (string $label, $value) => '<tr><td class="field">'.$field($label).'</td>'
            .'<td class="value">'.$field($value).'</td></tr>';

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
            <tr><td class="label">Nomor</td><td class="sep">:</td><td>'.$nomor.'</td></tr>
            <tr><td class="label">Sifat</td><td class="sep">:</td><td>Segera</td></tr>
            <tr><td class="label">Lampiran</td><td class="sep">:</td><td>-</td></tr>
            <tr><td class="label">Perihal</td><td class="sep">:</td><td>Permohonan Tindak Lanjut Kendala Pelaporan WLKP</td></tr>
        </table>

        <div class="recipient">
            <div class="bold">Kepada Yth.</div>
            <div>Direktur Bina Sistem Pengawasan Ketenagakerjaan</div>
            <div>di Jakarta</div>
        </div>

        <div class="opening">Dengan hormat,</div>

        <div class="body-paragraph">
            Kami yang bertanda tangan di bawah ini, selaku perwakilan '.$field($company?->nama_perusahaan ?: 'perusahaan').'
            bermaksud menyampaikan permohonan tindak lanjut kendala pelaporan WLKP,
            dengan rincian data sebagai berikut:
        </div>

        <h3 class="section-title">Data Pemohon:</h3>
        <table class="data-table">
            '.$row('Nama Pemohon', $complainant?->nama_lengkap).'
            '.$row('NIK', $complainant?->nik).'
            '.$row('Alamat', $complainant?->alamat).'
            '.$row('Jenis Kelamin', $complainant?->jenis_kelamin).'
            '.$row('Jabatan', $complainant?->jabatan).'
            '.$row('Telepon', $complainant?->no_telp).'
            '.$row('Email', $complainant?->email).'
        </table>

        <h3 class="section-title">Data Perusahaan:</h3>
        <table class="data-table">
            '.$row('Nama Perusahaan', $company?->nama_perusahaan).'
            '.$row('Sektor Industri', $sektorIndustri).'
            '.$row('Alamat Perusahaan', $company?->alamat).'
            '.$row('Jumlah Tenaga Kerja', $company?->jumlah_naker).'
            '.$row('Provinsi', $titleCase($company?->provinsi)).'
            '.$row('Kabupaten/Kota', $titleCase($company?->kota_kab)).'
            '.$row('Kecamatan', $titleCase($company?->kecamatan)).'
            '.$row('Kelurahan', $titleCase($company?->kelurahan)).'
            '.$row('Telepon Perusahaan', $company?->no_telp).'
            '.$row('Email Perusahaan', $company?->email).'
        </table>

        <h3 class="section-title">Kendala Pelaporan:</h3>
        <div class="kendala-text">'.$field($complaint->description).'</div>

        <div class="closing">
            Demikian surat ini kami sampaikan. Atas perhatian dan tindak lanjutnya,
            kami sampaikan ucapan terima kasih.
        </div>

        <table class="footer-table">
            <tr>
                <td class="footer-photo">
                    '.$photoHtml.'
                    <div class="caption">Foto Pemohon</div>
                </td>
                <td class="footer-sign">
                    <div class="place-date">'.$kotaSurat.', '.$tanggalSurat.'</div>
                    <div class="regards">Hormat kami,</div>
                    <div class="name">'.$field($complainant?->nama_lengkap).'</div>
                    <div>No. Hp: '.$field($complainant?->no_telp).'</div>
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
            'Content-Disposition' => 'attachment; filename="complaint-'.$complaint->id.'.pdf"',
        ]);
    }

    public function update(UpdateComplaintRequest $request, Complaint $complaint)
    {
        $oldStatus = $complaint->status;

        $complaint->update($request->validated());

        if ($complaint->wasChanged('status')) {
            $complaint->updates()->create([
                'old_status' => $oldStatus,
                'new_status' => $complaint->status,
                'notes' => $request->validated('notes'),
            ]);
        }

        return new ComplaintResource($complaint->load(['complainant', 'company.sector', 'category', 'assignedAdmin', 'attachments', 'updates']));
    }

    public function destroy(Complaint $complaint)
    {
        $complaint->delete();

        return response()->json(['success' => true, 'message' => 'Aduan dihapus']);
    }

    private function nextTicketNumber(): string
    {
        return 'TCK-'.strtoupper(uniqid());
    }
}
