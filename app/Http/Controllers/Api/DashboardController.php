<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ReportService;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __construct(
        protected ReportService $reportService
    ) {}

    public function overview()
    {
        return response()->json([
            'success' => true,
            'data' => [
                'total_complaints' => $this->reportService->getTotalComplaints(),
                'complaints_by_status' => $this->reportService->getComplaintsByStatus(),
                'complaints_by_category' => $this->reportService->getComplaintsByCategory(),
                'complaints_by_company_size' => $this->reportService->getComplaintsByCompanySize(),
                'recent_complaints' => $this->reportService->getRecentComplaints(5),
            ],
        ]);
    }

    public function reportByCategory(Request $request)
    {
        $data = $this->reportService->getComplaintsByCategoryWithDateRange(
            $request->query('start_date'),
            $request->query('end_date')
        );

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    public function reportByCompanySize(Request $request)
    {
        $data = $this->reportService->getComplaintsByCompanySizeWithDateRange(
            $request->query('start_date'),
            $request->query('end_date')
        );

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    public function exportToCsv(Request $request)
    {
        return $this->reportService->exportComplaintsCsv(
            $request->query()
        );
    }

    public function exportToPdf(Request $request)
    {
        return $this->reportService->exportComplaintsPdf(
            $request->query()
        );
    }
}
