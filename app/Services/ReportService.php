<?php

namespace App\Services;

use App\Models\CompanySize;
use App\Models\Complaint;
use App\Models\ComplaintCategory;
use Carbon\Carbon;
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
            'Content-Disposition' => 'attachment; filename="laporan_pengaduan_'.now()->format('Ymd_His').'.csv"',
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
}
