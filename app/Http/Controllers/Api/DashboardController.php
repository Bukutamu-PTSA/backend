<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DashboardController extends Controller
{
    public function __construct(private readonly ReportService $reportService) {}

    /**
     * Ringkasan analitik untuk dashboard admin.
     */
    public function overview(Request $request): JsonResponse
    {
        $filters = $this->filters($request);

        return response()->json([
            'success' => true,
            'data' => $this->reportService->analytics($filters),
            'meta' => ['filters' => $filters],
        ]);
    }

    /**
     * Ekspor laporan aduan sebagai CSV.
     */
    public function exportCsv(Request $request): StreamedResponse
    {
        return $this->reportService->exportCsv($this->filters($request));
    }

    /**
     * Ekspor laporan analitik sebagai PDF.
     */
    public function exportPdf(Request $request): StreamedResponse
    {
        return $this->reportService->exportPdf($this->filters($request));
    }

    /**
     * @return array<string, int>
     */
    private function filters(Request $request): array
    {
        $validated = $request->validate([
            'year' => 'nullable|integer|between:1900,2200',
            'month' => 'nullable|integer|between:1,12',
        ]);

        return array_filter([
            'year' => $validated['year'] ?? null,
            'month' => $validated['month'] ?? null,
        ], fn ($value) => $value !== null);
    }
}
