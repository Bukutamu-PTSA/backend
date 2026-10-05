<?php

namespace App\Services;

use App\Models\CompanySize;
use App\Models\Complaint;
use App\Models\ComplaintCategory;
use Carbon\Carbon;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportService
{
    public function getTotalComplaints(): int
    {
        return Complaint::count();
    }

    public function getComplaintsByStatus(): array
    {
        return Complaint::query()
            ->select('status', DB::raw('COUNT(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status')
            ->toArray();
    }

    public function getComplaintsByCategory(): array
    {
        return ComplaintCategory::query()
            ->withCount('complaints')
            ->orderBy('id')
            ->get()
            ->pluck('complaints_count', 'category_name')
            ->toArray();
    }

    public function getComplaintsByCompanySize(): array
    {
        return CompanySize::query()
            ->leftJoin('companies', function ($join) {
                $join->on(
                    'companies.jumlah_naker',
                    '>=',
                    'company_sizes.min_employees'
                )
                    ->where(function ($query) {
                        $query->whereNull('company_sizes.max_employees')
                            ->orWhereColumn(
                                'companies.jumlah_naker',
                                '<=',
                                'company_sizes.max_employees'
                            );
                    });
            })
            ->leftJoin(
                'complaints',
                'complaints.company_id',
                '=',
                'companies.id'
            )
            ->select(
                'company_sizes.size_name',
                DB::raw('COUNT(complaints.id) as total')
            )
            ->groupBy(
                'company_sizes.id',
                'company_sizes.size_name'
            )
            ->orderBy('company_sizes.id')
            ->pluck('total', 'size_name')
            ->toArray();
    }

    public function getRecentComplaints(int $limit = 5)
    {
        return Complaint::query()
            ->with([
                'complainant',
                'company',
                'category',
            ])
            ->latest('complaint_date')
            ->limit($limit)
            ->get();
    }

    public function getComplaintsByCategoryWithDateRange(
        ?string $startDate,
        ?string $endDate
    ) {
        $query = Complaint::query();

        if ($startDate && $endDate) {
            $query->whereBetween('complaint_date', [
                Carbon::parse($startDate)->startOfDay(),
                Carbon::parse($endDate)->endOfDay(),
            ]);
        }

        return $query
            ->select(
                'category_id',
                DB::raw('COUNT(*) as total')
            )
            ->with('category:id,category_name')
            ->groupBy('category_id')
            ->get();
    }

    public function getComplaintsByCompanySizeWithDateRange(
        ?string $startDate,
        ?string $endDate
    ) {
        $query = CompanySize::query()
            ->leftJoin('companies', function ($join) {
                $join->on(
                    'companies.jumlah_naker',
                    '>=',
                    'company_sizes.min_employees'
                )
                    ->where(function ($query) {
                        $query->whereNull('company_sizes.max_employees')
                            ->orWhereColumn(
                                'companies.jumlah_naker',
                                '<=',
                                'company_sizes.max_employees'
                            );
                    });
            })
            ->leftJoin('complaints', function ($join) use ($startDate, $endDate) {
                $join->on(
                    'complaints.company_id',
                    '=',
                    'companies.id'
                );

                if ($startDate && $endDate) {
                    $join->whereBetween('complaints.complaint_date', [
                        Carbon::parse($startDate)->startOfDay(),
                        Carbon::parse($endDate)->endOfDay(),
                    ]);
                }
            });

        return $query
            ->select(
                'company_sizes.size_name',
                DB::raw('COUNT(complaints.id) as total')
            )
            ->groupBy(
                'company_sizes.id',
                'company_sizes.size_name'
            )
            ->orderBy('company_sizes.id')
            ->pluck('total', 'size_name')
            ->toArray();
    }

    public function exportComplaintsCsv(array $filters): StreamedResponse
    {
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="laporan-pengaduan-'.now()->format('Ymd_His').'.csv"; filename*=UTF-8\'\'laporan-pengaduan-'.now()->format('Ymd_His').'.csv',
        ];

        return response()->stream(function () use ($filters) {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, [
                'Nomor Tiket',
                'Deskripsi',
                'Status',
                'Prioritas',
                'Pelapor',
                'Perusahaan',
                'Kategori',
                'Tanggal',
            ]);

            $query = Complaint::query()
                ->with([
                    'complainant',
                    'company',
                    'category',
                ]);

            if (! empty($filters['status'])) {
                $query->where('status', $filters['status']);
            }

            if (! empty($filters['start_date']) && ! empty($filters['end_date'])) {
                $query->whereBetween('complaint_date', [
                    Carbon::parse($filters['start_date'])->startOfDay(),
                    Carbon::parse($filters['end_date'])->endOfDay(),
                ]);
            }

            $query->chunk(200, function ($complaints) use ($handle) {
                foreach ($complaints as $complaint) {
                    fputcsv($handle, [
                        $complaint->ticket_number,
                        $complaint->description,
                        $complaint->status,
                        $complaint->priority,
                        $complaint->complainant->nama_lengkap ?? '-',
                        $complaint->company->nama_perusahaan ?? '-',
                        $complaint->category->category_name ?? '-',
                        $complaint->complaint_date?->format('Y-m-d H:i'),
                    ]);
                }
            });

            fclose($handle);
        }, 200, $headers);
    }

    public function exportComplaintsPdf(array $filters): StreamedResponse
    {
        $headers = [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="laporan-pengaduan-'.now()->format('Ymd_His').'.pdf"; filename*=UTF-8\'\'laporan-pengaduan-'.now()->format('Ymd_His').'.pdf',
        ];

        return response()->stream(function () use ($filters) {
            $data = $this->getReportData($filters);

            $options = new Options;
            $options->set('isRemoteEnabled', false);
            $options->set('defaultFont', 'DejaVu Sans');

            $dompdf = new Dompdf($options);
            $dompdf->loadHtml($this->buildPdfHtml($data), 'UTF-8');
            $dompdf->setPaper('A4', 'portrait');
            $dompdf->render();

            echo $dompdf->output();
        }, 200, $headers);
    }

    private function getReportData(array $filters): array
    {
        $totals = $this->getComplaintsByCategoryWithDateRange(
            $filters['start_date'] ?? null,
            $filters['end_date'] ?? null
        )->pluck('total', 'category.category_name');

        $byCategory = ComplaintCategory::query()
            ->orderBy('id')
            ->pluck('category_name')
            ->mapWithKeys(fn ($name) => [$name => (int) $totals->get($name, 0)])
            ->all();

        $byCompanySize = $this->getComplaintsByCompanySizeWithDateRange(
            $filters['start_date'] ?? null,
            $filters['end_date'] ?? null
        );

        return compact('byCategory', 'byCompanySize', 'filters');
    }

    private function buildPdfHtml(array $data): string
    {
        $period = 'Semua Waktu';
        if (! empty($data['filters']['start_date']) && ! empty($data['filters']['end_date'])) {
            $period = Carbon::parse($data['filters']['start_date'])->format('d M Y')
                .' s.d. '
                .Carbon::parse($data['filters']['end_date'])->format('d M Y');
        }

        $printedAt = now()->format('d M Y H:i');

        $categoryChart = $this->renderBarChart('Grafik Jumlah Pengaduan per Kategori', $data['byCategory']);
        $sizeChart = $this->renderBarChart('Grafik Jumlah Pengaduan per Ukuran Perusahaan', $data['byCompanySize']);

        return <<<HTML
<!doctype html>
<html><head><meta charset="UTF-8"><style>
body { font-family: DejaVu Sans, sans-serif; font-size: 10.5pt; color: #1f2d3d; line-height: 1.45; }
h1 { font-size: 16pt; margin: 0 0 3px; text-align: center; color: #2b3a55; }
.meta { text-align: center; font-size: 9pt; color: #6b7c8c; }
.rule { border-bottom: 2px solid #2b3a55; margin: 10px 0 16px; }

.chart-card { border:1px solid #dde3e9; border-radius:8px; padding:14px 16px; margin-bottom:16px; page-break-inside:avoid; }
.chart-head { width:100%; border-collapse:collapse; margin-bottom:12px; }
.chart-head td { border-bottom:1px solid #eef1f4; padding-bottom:7px; }
.chart-title { font-size:11pt; font-weight:bold; color:#2b3a55; }
.chart-sub { font-size:8pt; color:#8a9aa8; text-align:right; }

.bars-table { width:100%; border-collapse:collapse; }
.bar-label { width:34%; font-size:9pt; color:#33475b; padding:4px 10px 4px 0; }
.bar-track { height:18px; background-color:#f4f7f9; vertical-align:middle; }
.bar { height:10px; background:#4a6d8c; border-radius:2px; }
.bar-value { width:45px; font-size:9pt; color:#5b6b7b; text-align:right; }
.bar-total td { border-top:1px solid #eef1f4; padding-top:6px; font-size:9pt; font-weight:bold; color:#2b3a55; }
.chart-empty { margin:0; font-size:9pt; color:#8a9aa8; }

@page { margin: 18mm 20mm; }
</style></head><body>

<h1>Laporan Rekap Pengaduan</h1>
<div class="meta">Periode: {$period} &nbsp;|&nbsp; Dicetak: {$printedAt}</div>
<div class="rule"></div>

{$categoryChart}

{$sizeChart}

</body></html>
HTML;
    }

    private function renderBarChart(string $title, array $values): string
    {
        $escapedTitle = htmlspecialchars($title, ENT_QUOTES, 'UTF-8');

        if (empty($values)) {
            return '<div class="chart-card">
            <table class="chart-head"><tr>
                <td class="chart-title">'.$escapedTitle.'</td>
                <td class="chart-sub">Tidak ada data</td>
            </tr></table>
            <p class="chart-empty">Belum ada kategori terdaftar.</p>
        </div>';
        }

        $axisMax = $this->niceAxisMax(max(1, (int) max($values)));

        $rowsHtml = '';
        $total = 0;
        foreach ($values as $label => $count) {
            $count = (int) $count;
            $total += $count;
            $barWidth = $count > 0 ? max((int) round(($count / $axisMax) * 100), 1) : 0;
            $escapedLabel = htmlspecialchars((string) $label, ENT_QUOTES, 'UTF-8');

            $rowsHtml .= '<tr>
            <td class="bar-label">'.$escapedLabel.'</td>
            <td class="bar-track"><div class="bar" style="width:'.$barWidth.'%;"></div></td>
            <td class="bar-value">'.$count.'</td>
        </tr>';
        }

        $rowsHtml .= '<tr class="bar-total">
            <td class="bar-label">Total</td>
            <td class="bar-track"></td>
            <td class="bar-value">'.$total.'</td>
        </tr>';

        return <<<HTML
    <div class="chart-card">
        <table class="chart-head"><tr>
            <td class="chart-title">{$escapedTitle}</td>
            <td class="chart-sub">Skala 0 - {$axisMax}</td>
        </tr></table>
        <table class="bars-table">
            {$rowsHtml}
        </table>
    </div>
    HTML;
    }

    private function niceAxisMax(int $max): int
    {
        if ($max <= 10) {
            return 10;
        }

        $magnitude = (int) pow(10, floor(log10($max)));
        $residual = $max / $magnitude;

        $niceResidual = match (true) {
            $residual <= 1 => 1,
            $residual <= 2 => 2,
            $residual <= 5 => 5,
            default => 10,
        };

        return $niceResidual * $magnitude;
    }
}
