<?php

namespace App\Services;

use App\Models\Complaint;
use App\Models\Survey;
use Dompdf\Dompdf;
use Dompdf\Options;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportService
{
    /**
     * Kumpulan agregasi analitik untuk dashboard & laporan.
     *
     * @param  array<string, mixed>  $filters  (year, month opsional)
     * @return array<string, mixed>
     */
    public function analytics(array $filters = []): array
    {
        $scope = fn () => $this->baseQuery($filters);

        return [
            'total_complaints' => $scope()->count(),
            'by_category' => $this->byCategory($filters),
            'by_sector' => $this->bySector($filters),
            'by_status' => $this->byStatus($filters),
            'monthly_trend' => $this->monthlyTrend($filters),
            'average_satisfaction' => $this->averageSatisfaction($filters),
        ];
    }

    /**
     * Aduan per kategori pelayanan.
     *
     * @param  array<string, mixed>  $filters
     * @return array<int, array<string, mixed>>
     */
    public function byCategory(array $filters = []): array
    {
        return $this->baseQuery($filters)
            ->leftJoin('complaint_categories', 'complaint_categories.id', '=', 'complaints.category_id')
            ->selectRaw('COALESCE(complaint_categories.category_name, ?) AS label, COUNT(complaints.id) AS total', ['Tidak dikategorikan'])
            ->groupBy('complaint_categories.category_name')
            ->orderByDesc('total')
            ->get()
            ->map(fn ($row) => ['label' => $row->label, 'total' => (int) $row->total])
            ->all();
    }

    /**
     * Aduan per sektor industri (via perusahaan).
     *
     * @param  array<string, mixed>  $filters
     * @return array<int, array<string, mixed>>
     */
    public function bySector(array $filters = []): array
    {
        return $this->baseQuery($filters)
            ->join('companies', 'companies.id', '=', 'complaints.company_id')
            ->leftJoin('industrial_sectors', 'industrial_sectors.id', '=', 'companies.sector_id')
            ->selectRaw('COALESCE(industrial_sectors.sector_name, ?) AS label, COUNT(complaints.id) AS total', ['Tidak ditentukan'])
            ->groupBy('industrial_sectors.sector_name')
            ->orderByDesc('total')
            ->get()
            ->map(fn ($row) => ['label' => $row->label, 'total' => (int) $row->total])
            ->all();
    }

    /**
     * Aduan per status.
     *
     * @param  array<string, mixed>  $filters
     * @return array<int, array<string, mixed>>
     */
    public function byStatus(array $filters = []): array
    {
        return $this->baseQuery($filters)
            ->selectRaw('status AS label, COUNT(id) AS total')
            ->groupBy('status')
            ->orderByDesc('total')
            ->get()
            ->map(fn ($row) => ['label' => $row->label, 'total' => (int) $row->total])
            ->all();
    }

    /**
     * Tren jumlah aduan per bulan pada tahun tertentu (default tahun berjalan).
     *
     * @param  array<string, mixed>  $filters
     * @return array<int, array<string, mixed>>
     */
    public function monthlyTrend(array $filters = []): array
    {
        $year = (int) ($filters['year'] ?? now()->year);

        $counts = Complaint::query()
            ->whereYear('complaint_date', $year)
            ->selectRaw('EXTRACT(MONTH FROM complaint_date) AS month, COUNT(id) AS total')
            ->groupByRaw('EXTRACT(MONTH FROM complaint_date)')
            ->pluck('total', 'month');

        return collect(range(1, 12))->map(fn (int $month) => [
            'month' => $month,
            'total' => (int) ($counts[$month] ?? 0),
        ])->all();
    }

    /**
     * Rata-rata skor kepuasan dari survei (rating_overall 1–5).
     *
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function averageSatisfaction(array $filters = []): array
    {
        $query = Survey::query()->whereNotNull('rating_overall');

        if (! empty($filters['year'])) {
            $query->whereYear('survey_date', (int) $filters['year']);
        }

        $count = (clone $query)->count();
        $avg = $count > 0 ? round((float) (clone $query)->avg('rating_overall'), 2) : null;

        return [
            'average' => $avg,
            'scale' => 5,
            'responses' => $count,
        ];
    }

    /**
     * Ekspor ringkasan aduan (per kategori) ke CSV streaming.
     */
    public function exportCsv(array $filters = []): StreamedResponse
    {
        $rows = $this->baseQuery($filters)
            ->join('complainants', 'complainants.id', '=', 'complaints.complainant_id')
            ->join('companies', 'companies.id', '=', 'complaints.company_id')
            ->leftJoin('complaint_categories', 'complaint_categories.id', '=', 'complaints.category_id')
            ->orderBy('complaints.id')
            ->get([
                'complaints.ticket_number',
                'complaints.status',
                'complaints.priority',
                'complaints.complaint_date',
                'complainants.nama_lengkap AS pelapor',
                'companies.nama_perusahaan AS perusahaan',
                'complaint_categories.category_name AS kategori',
            ]);

        $filename = 'laporan-aduan-'.now()->format('Ymd_His').'.csv';

        return response()->streamDownload(function () use ($rows) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['No Tiket', 'Status', 'Prioritas', 'Tanggal', 'Pelapor', 'Perusahaan', 'Kategori'], ',', '"', '\\');

            foreach ($rows as $row) {
                fputcsv($handle, [
                    $row->ticket_number,
                    $row->status,
                    $row->priority,
                    $row->complaint_date,
                    $row->pelapor,
                    $row->perusahaan,
                    $row->kategori ?? 'Tidak dikategorikan',
                ], ',', '"', '\\');
            }

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    /**
     * Ekspor ringkasan analitik ke PDF.
     */
    public function exportPdf(array $filters = []): StreamedResponse
    {
        $data = $this->analytics($filters);
        $html = $this->buildPdfHtml($data, $filters);

        $dompdf = new Dompdf((new Options)->setIsRemoteEnabled(false));
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper('A4');
        $dompdf->render();

        $filename = 'laporan-analitik-'.now()->format('Ymd_His').'.pdf';

        return response()->streamDownload(
            fn () => print ($dompdf->output()),
            $filename,
            ['Content-Type' => 'application/pdf'],
        );
    }

    /**
     * Query dasar aduan dengan filter tahun/bulan opsional.
     *
     * @param  array<string, mixed>  $filters
     */
    private function baseQuery(array $filters)
    {
        return Complaint::query()
            ->when(! empty($filters['year']), fn ($query) => $query->whereYear('complaint_date', (int) $filters['year']))
            ->when(! empty($filters['month']), fn ($query) => $query->whereMonth('complaint_date', (int) $filters['month']));
    }

    /**
     * Susun HTML sederhana untuk laporan PDF.
     *
     * @param  array<string, mixed>  $data
     * @param  array<string, mixed>  $filters
     */
    private function buildPdfHtml(array $data, array $filters): string
    {
        $esc = fn ($value) => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
        $period = ($filters['year'] ?? 'Semua Tahun').(isset($filters['month']) ? ' / Bulan '.$filters['month'] : '');

        $section = function (string $title, array $rows) use ($esc): string {
            $body = '';
            foreach ($rows as $row) {
                $body .= '<tr><td>'.$esc($row['label']).'</td><td style="text-align:right">'.$esc($row['total']).'</td></tr>';
            }

            return '<h3>'.$esc($title).'</h3><table><tr><th>Label</th><th>Total</th></tr>'.$body.'</table>';
        };

        $satisfaction = $data['average_satisfaction'];

        return '<!doctype html><html><head><meta charset="UTF-8"><style>
            body { font-family: DejaVu Sans, sans-serif; font-size: 11pt; color: #111; }
            h1 { font-size: 16pt; } h3 { margin-top: 18px; }
            table { width: 100%; border-collapse: collapse; margin-top: 6px; }
            th, td { border: 1px solid #999; padding: 5px 8px; text-align: left; }
        </style></head><body>
            <h1>Laporan Analitik Pengaduan PTSA</h1>
            <p>Periode: '.$esc($period).'<br>Total Aduan: '.$esc($data['total_complaints']).'<br>
            Rata-rata Kepuasan: '.$esc($satisfaction['average'] ?? '-').' / '.$esc($satisfaction['scale']).'
            ('.$esc($satisfaction['responses']).' responden)</p>
            '.$section('Aduan per Kategori', $data['by_category']).'
            '.$section('Aduan per Sektor Industri', $data['by_sector']).'
            '.$section('Aduan per Status', $data['by_status']).'
        </body></html>';
    }
}
