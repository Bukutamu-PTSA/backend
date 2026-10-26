<?php

use App\Http\Controllers\Api\AttachmentController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\CompanyController;
use App\Http\Controllers\Api\CompanySizeController;
use App\Http\Controllers\Api\ComplainantController;
use App\Http\Controllers\Api\ComplaintCategoryController;
use App\Http\Controllers\Api\ComplaintController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\FaqController;
use App\Http\Controllers\Api\ScalarDocsController;
use App\Http\Controllers\Api\SettingController;
use App\Http\Controllers\Api\SurveyController;
use App\Http\Controllers\Api\WilayahController;
use App\Models\IndustrialSector;
use Illuminate\Support\Facades\Route;

Route::get('/docs', [ScalarDocsController::class, 'index']);
Route::get('/docs/openapi', [ScalarDocsController::class, 'openapi']);

Route::get('/provinces', [WilayahController::class, 'getProvinces']);
Route::get('/cities/{province_code}', [WilayahController::class, 'getCities']);
Route::get('/districts/{city_code}', [WilayahController::class, 'getDistricts']);
Route::get('/villages/{district_code}', [WilayahController::class, 'getVillages']);

Route::apiResource('complaint-categories', ComplaintCategoryController::class);
Route::apiResource('faqs', FaqController::class);
Route::get('industrial-sectors', fn() => IndustrialSector::orderBy('id')->get(['id', 'sector_code', 'sector_name']));

Route::post('complaints', [ComplaintController::class, 'store']);
Route::get('dashboard/province-summary', [ComplaintController::class, 'provinceSummary']);
Route::get('dashboard/complaint-summary', [ComplaintController::class, 'summary']);
Route::apiResource('complaints', ComplaintController::class)->except('store');
Route::get('complaints/{complaint}/attachments/{attachment}/file', [AttachmentController::class, 'file'])
    ->middleware('signed:relative')
    ->name('complaints.attachments.file');
Route::post('complaints/{complaint}/attachments', [AttachmentController::class, 'store']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('dashboard/province-summary/pdf', [ComplaintController::class, 'provinceSummaryPdf']);
    Route::get('complaints/{complaint}/pdf', [ComplaintController::class, 'pdf']);
    Route::get('complaints/{complaint}/attachments', [AttachmentController::class, 'index']);
    Route::delete('complaints/{complaint}/attachments/{attachment}', [AttachmentController::class, 'destroy']);
    Route::get('dashboard/statistics', [ComplaintController::class, 'statistics']);
    // Survey protected routes (Admin Only)
    Route::get('/surveys/responses', [SurveyController::class, 'responses'])
        ->middleware('role:admin,super_admin');
    Route::get('/surveys/responses/pdf', [SurveyController::class, 'pdf'])
        ->middleware('role:admin,super_admin');
    Route::patch('/surveys/{survey}/close', [SurveyController::class, 'close'])
        ->middleware('role:admin,super_admin');
    Route::put('/surveys/{survey}', [SurveyController::class, 'update'])
        ->middleware('role:admin,super_admin');
    Route::delete('/surveys/{survey}', [SurveyController::class, 'destroy'])
        ->middleware('role:admin,super_admin');
    // User management (Admin Only)
    Route::get('/users', [AuthController::class, 'index'])
        ->middleware('role:admin,super_admin');
    Route::get('/users/pdf', [AuthController::class, 'pdf'])
        ->middleware('role:admin,super_admin');
    Route::get('/users/{user}', [AuthController::class, 'show'])
        ->middleware('role:admin,super_admin');
    Route::put('/users/{user}', [AuthController::class, 'update'])
        ->middleware('role:admin,super_admin');
    Route::delete('/users/{user}', [AuthController::class, 'destroy'])
        ->middleware('role:admin,super_admin');
});

Route::prefix('v1')->group(function () {
    // Public Routes (Auth)
    Route::post('/auth/login', [AuthController::class, 'login']);
    Route::post('/auth/register', [AuthController::class, 'register']);

    //  Protected Routes (Wajib Login / Bearer Token)
    Route::middleware('auth:sanctum')->group(function () {
        // Auth User & Logout
        Route::get('/auth/me', [AuthController::class, 'me']);
        Route::post('/auth/logout', [AuthController::class, 'logout']);

        // Master Data (Read & Create untuk User/Pelapor/Admin)
        Route::get('/categories', [CategoryController::class, 'index']);
        Route::get('/categories/pdf', [CategoryController::class, 'pdf']);
        Route::get('/company-sizes', [CompanySizeController::class, 'index']);
        Route::get('/companies', [CompanyController::class, 'index']);
        Route::post('/companies', [CompanyController::class, 'store']);
        Route::get('/complainants', [ComplainantController::class, 'index']);

        // 🛡️ Admin Only Routes (Khusus Super Admin & Admin)
        Route::middleware('role:super_admin,admin')->group(function () {
            Route::post('/admin/categories', [CategoryController::class, 'store']);
            Route::post('/admin/company-sizes', [CompanySizeController::class, 'store']);
            Route::put('/admin/company-sizes/{companySize}', [CompanySizeController::class, 'update']);
            Route::get('/admin/settings', [SettingController::class, 'index']);
            Route::put('/admin/settings/{setting}', [SettingController::class, 'update']);
        });
    });

    // Dashboard & Report Routes (Admin Only)
    Route::middleware(['auth:sanctum', 'role:admin,super_admin'])->group(function () {
        Route::get('/dashboard/overview', [DashboardController::class, 'overview']);
        Route::get('/reports/by-category', [DashboardController::class, 'reportByCategory']);
        Route::get('/reports/by-company-size', [DashboardController::class, 'reportByCompanySize']);
        Route::get('/reports/export/csv', [DashboardController::class, 'exportToCsv']);
        Route::get('/reports/export/pdf', [DashboardController::class, 'exportToPdf']);
    });
});

// Public Survey routes
Route::get('/surveys/form', [SurveyController::class, 'form']);
Route::post('/surveys', [SurveyController::class, 'submit']);
Route::get('/surveys/satisfaction', [SurveyController::class, 'satisfaction']);
Route::get('/surveys/{survey}', [SurveyController::class, 'show']);
Route::post('/surveys/{survey}/responses', [SurveyController::class, 'storeResponses']);
