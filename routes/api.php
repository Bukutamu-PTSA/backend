<?php

use App\Http\Controllers\Api\AttachmentController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\CompanyController;
use App\Http\Controllers\Api\CompanySizeController;
use App\Http\Controllers\Api\ComplaintCategoryController;
use App\Http\Controllers\Api\ComplaintController;
use App\Http\Controllers\Api\SettingController;
use App\Http\Controllers\Api\WilayahController;
use App\Models\IndustrialSector;
use Illuminate\Support\Facades\Route;

Route::get('/provinces', [WilayahController::class, 'getProvinces']);
Route::get('/cities/{province_code}', [WilayahController::class, 'getCities']);
Route::get('/districts/{city_code}', [WilayahController::class, 'getDistricts']);
Route::get('/villages/{district_code}', [WilayahController::class, 'getVillages']);

Route::apiResource('complaint-categories', ComplaintCategoryController::class);
Route::get('industrial-sectors', fn() => IndustrialSector::orderBy('id')->get(['id', 'sector_code', 'sector_name']));

Route::post('complaints', [ComplaintController::class, 'store']);
Route::get('dashboard/province-summary', [ComplaintController::class, 'provinceSummary']);
Route::get('dashboard/statistics', [ComplaintController::class, 'statistics']);
Route::get('complaints/{complaint}/pdf', [ComplaintController::class, 'pdf']);
Route::apiResource('complaints', ComplaintController::class)->except('store');
Route::get('complaints/{complaint}/attachments', [AttachmentController::class, 'index']);
Route::post('complaints/{complaint}/attachments', [AttachmentController::class, 'store']);
Route::delete('complaints/{complaint}/attachments/{attachment}', [AttachmentController::class, 'destroy']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('dashboard/complaint-summary', [ComplaintController::class, 'summary']);
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
        Route::get('/company-sizes', [CompanySizeController::class, 'index']);
        Route::get('/companies', [CompanyController::class, 'index']);
        Route::post('/companies', [CompanyController::class, 'store']);

        // 🛡️ Admin Only Routes (Khusus Super Admin & Admin)
        Route::middleware('role:super_admin,admin')->group(function () {
            Route::post('/admin/categories', [CategoryController::class, 'store']);
            Route::post('/admin/company-sizes', [CompanySizeController::class, 'store']);
            Route::get('/admin/settings', [SettingController::class, 'index']);
            Route::put('/admin/settings/{setting}', [SettingController::class, 'update']);
        });
    });
});
