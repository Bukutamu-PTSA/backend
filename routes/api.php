<?php

use App\Http\Controllers\Api\AttachmentController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ComplaintController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\ScalarDocsController;
use App\Http\Controllers\Api\SurveyController;
use App\Http\Controllers\Api\WilayahController;
use App\Models\ComplaintCategory;
use App\Models\IndustrialSector;
use Illuminate\Support\Facades\Route;

// Scalar Documentation routes
Route::get('/docs', [ScalarDocsController::class, 'index']);
Route::get('/docs/openapi', [ScalarDocsController::class, 'openapi']);

// Authentication routes
Route::prefix('v1')->group(function () {
    // Public Routes
    Route::post('/auth/login', [AuthController::class, 'login']);
    Route::post('/auth/register', [AuthController::class, 'register']);

    // Protected Routes
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/auth/me', [AuthController::class, 'me']);
        Route::post('/auth/logout', [AuthController::class, 'logout']);
    });

    // Dashboard & Report Routes
    Route::middleware(['auth:sanctum', 'role:admin,super_admin'])->group(function () {
        Route::get('/dashboard/overview', [DashboardController::class, 'overview']);
        Route::get('/reports/by-category', [DashboardController::class, 'reportByCategory']);
        Route::get('/reports/by-company-size', [DashboardController::class, 'reportByCompanySize']);
        Route::get('/reports/export/csv', [DashboardController::class, 'exportToCsv']);
    });
});

// Public Survey routes
Route::get('/surveys/{survey}', [SurveyController::class, 'show']);
Route::post('/surveys/{survey}/responses', [SurveyController::class, 'storeResponses']);

Route::get('/provinces', [WilayahController::class, 'getProvinces']);
Route::get('/cities/{province_code}', [WilayahController::class, 'getCities']);
Route::get('/districts/{city_code}', [WilayahController::class, 'getDistricts']);
Route::get('/villages/{district_code}', [WilayahController::class, 'getVillages']);

Route::get('complaint-categories', fn () => ComplaintCategory::orderBy('category_name')->get(['id', 'category_code', 'category_name']));
Route::get('industrial-sectors', fn () => IndustrialSector::orderBy('id')->get(['id', 'sector_code', 'sector_name']));

Route::post('complaints', [ComplaintController::class, 'store']);

Route::middleware('auth:sanctum')->group(function () {
    // Survey protected routes
    Route::post('/surveys', [SurveyController::class, 'store'])
        ->middleware('role:admin,super_admin');

    Route::patch('/surveys/{survey}/close', [SurveyController::class, 'close'])
        ->middleware('role:admin,super_admin');

    Route::get('dashboard/complaint-summary', [ComplaintController::class, 'summary']);
    Route::apiResource('complaints', ComplaintController::class)->except('store');
    Route::get('complaints/{complaint}/attachments', [AttachmentController::class, 'index']);
    Route::post('complaints/{complaint}/attachments', [AttachmentController::class, 'store']);
    Route::delete('complaints/{complaint}/attachments/{attachment}', [AttachmentController::class, 'destroy']);
});