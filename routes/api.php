<?php

use App\Http\Controllers\Api\AttachmentController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\CompanyController;
use App\Http\Controllers\Api\CompanySizeController;
use App\Http\Controllers\Api\ComplaintCategoryController;
use App\Http\Controllers\Api\ComplaintController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\ScalarDocsController;
use App\Http\Controllers\Api\SettingController;
use App\Http\Controllers\Api\SurveyController;
use App\Http\Controllers\Api\WilayahController;
use App\Models\IndustrialSector;
use Illuminate\Support\Facades\Route;

Route::get('/docs', [ScalarDocsController::class, 'index']);
Route::get('/docs/openapi', [ScalarDocsController::class, 'openapi']);
Route::get('/admin/settings', [SettingController::class, 'index']);
Route::get('/companies', [CompanyController::class, 'index']);

Route::prefix('v1')->group(function (): void {
    Route::post('/auth/login', [AuthController::class, 'login']);
    Route::post('/auth/register', [AuthController::class, 'register']);

    Route::middleware('auth:sanctum')->group(function (): void {
        Route::get('/auth/me', [AuthController::class, 'me']);
        Route::post('/auth/logout', [AuthController::class, 'logout']);
        Route::get('/categories', [CategoryController::class, 'index']);
        Route::get('/company-sizes', [CompanySizeController::class, 'index']);
        Route::post('/companies', [CompanyController::class, 'store']);

        Route::middleware('role:super_admin,admin')->group(function (): void {
            Route::post('/admin/categories', [CategoryController::class, 'store']);
            Route::post('/admin/company-sizes', [CompanySizeController::class, 'store']);
            Route::put('/admin/settings/{setting}', [SettingController::class, 'update']);
            Route::get('/dashboard/overview', [DashboardController::class, 'overview']);
            Route::get('/reports/by-category', [DashboardController::class, 'reportByCategory']);
            Route::get('/reports/by-company-size', [DashboardController::class, 'reportByCompanySize']);
            Route::get('/reports/export/csv', [DashboardController::class, 'exportToCsv']);
        });
    });
});

Route::get('/surveys/{survey}', [SurveyController::class, 'show']);
Route::post('/surveys/{survey}/responses', [SurveyController::class, 'storeResponses']);

Route::get('/provinces', [WilayahController::class, 'getProvinces']);
Route::get('/cities/{province_code}', [WilayahController::class, 'getCities']);
Route::get('/districts/{city_code}', [WilayahController::class, 'getDistricts']);
Route::get('/villages/{district_code}', [WilayahController::class, 'getVillages']);

Route::apiResource('complaint-categories', ComplaintCategoryController::class);
Route::get('industrial-sectors', fn () => IndustrialSector::orderBy('id')->get(['id', 'sector_code', 'sector_name']));

Route::post('complaints', [ComplaintController::class, 'store']);
Route::get('dashboard/complaint-summary', [ComplaintController::class, 'summary']);
Route::get('dashboard/province-summary', [ComplaintController::class, 'provinceSummary']);
Route::get('dashboard/statistics', [ComplaintController::class, 'statistics']);
Route::get('complaints/{complaint}/pdf', [ComplaintController::class, 'pdf']);
Route::apiResource('complaints', ComplaintController::class)->except('store');
Route::get('complaints/{complaint}/attachments', [AttachmentController::class, 'index']);
Route::post('complaints/{complaint}/attachments', [AttachmentController::class, 'store']);
Route::delete('complaints/{complaint}/attachments/{attachment}', [AttachmentController::class, 'destroy']);

Route::middleware('auth:sanctum')->group(function (): void {
    Route::post('/surveys', [SurveyController::class, 'store'])
        ->middleware('role:admin,super_admin');
    Route::patch('/surveys/{survey}/close', [SurveyController::class, 'close'])
        ->middleware('role:admin,super_admin');
});
