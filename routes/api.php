<?php

use App\Http\Controllers\Api\AttachmentController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CompanyController;
use App\Http\Controllers\Api\CompanySizeController;
use App\Http\Controllers\Api\ComplainantController;
use App\Http\Controllers\Api\ComplaintCategoryController;
use App\Http\Controllers\Api\ComplaintController;
use App\Http\Controllers\Api\ComplaintTypeController;
use App\Http\Controllers\Api\IndustrySectorController;
use App\Http\Controllers\Api\RoleController;
use App\Http\Controllers\Api\ServiceCategoryController;
use App\Http\Controllers\Api\SettingController;
use App\Http\Controllers\Api\WilayahController;
use App\Models\IndustrialSector;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    // Public Routes (Auth)
    Route::post('/auth/login', [AuthController::class, 'login']);
    Route::post('/auth/register', [AuthController::class, 'register']);

    //  Protected Routes (Wajib Login / Bearer Token)
    Route::middleware('auth:sanctum')->group(function () {
        // Auth User & Logout
        Route::get('/auth/me', [AuthController::class, 'me']);
        Route::post('/auth/logout', [AuthController::class, 'logout']);

        // Master Data (Read untuk User/Pelapor/Admin)
        Route::get('/service-categories', [ServiceCategoryController::class, 'index']);
        Route::get('/service-categories/{serviceCategory}', [ServiceCategoryController::class, 'show']);
        Route::get('/complaint-types', [ComplaintTypeController::class, 'index']);
        Route::get('/complaint-types/{complaintType}', [ComplaintTypeController::class, 'show']);
        Route::get('/industry-sectors', [IndustrySectorController::class, 'index']);
        Route::get('/company-sizes', [CompanySizeController::class, 'index']);

        // Perusahaan
        Route::get('/companies', [CompanyController::class, 'index']);
        Route::post('/companies', [CompanyController::class, 'store']);

        // Pelapor (Biodata) — dibutuhkan sebelum pembuatan tiket aduan
        Route::get('/complainants', [ComplainantController::class, 'index']);
        Route::get('/complainants/{complainant}', [ComplainantController::class, 'show']);
        Route::post('/complainants', [ComplainantController::class, 'store']);
        Route::put('/complainants/{complainant}', [ComplainantController::class, 'update']);
        Route::patch('/complainants/{complainant}', [ComplainantController::class, 'update']);

        // 🛡️ Admin Only Routes (Khusus Super Admin & Admin)
        Route::middleware('role:super_admin,admin')->group(function () {
            // Daftar role untuk manajemen/assign user
            Route::get('/admin/roles', [RoleController::class, 'index']);

            // Master Kategori Pelayanan (KATEGORI_PELAYANAN)
            Route::post('/admin/service-categories', [ServiceCategoryController::class, 'store']);
            Route::put('/admin/service-categories/{serviceCategory}', [ServiceCategoryController::class, 'update']);
            Route::delete('/admin/service-categories/{serviceCategory}', [ServiceCategoryController::class, 'destroy']);

            // Master Jenis Pengaduan (JENIS_PENGADUAN)
            Route::post('/admin/complaint-types', [ComplaintTypeController::class, 'store']);
            Route::put('/admin/complaint-types/{complaintType}', [ComplaintTypeController::class, 'update']);
            Route::delete('/admin/complaint-types/{complaintType}', [ComplaintTypeController::class, 'destroy']);

            // Master Sektor Industri (SEKTOR_INDUSTRI)
            Route::post('/admin/industry-sectors', [IndustrySectorController::class, 'store']);
            Route::put('/admin/industry-sectors/{industrialSector}', [IndustrySectorController::class, 'update']);
            Route::delete('/admin/industry-sectors/{industrialSector}', [IndustrySectorController::class, 'destroy']);

            // Master Skala Perusahaan (SKALA_PARAMETER_PERUSAHAAN)
            Route::post('/admin/company-sizes', [CompanySizeController::class, 'store']);

            // Pengaturan Sistem (APP_SETTINGS)
            Route::get('/admin/settings', [SettingController::class, 'index']);
            Route::put('/admin/settings/{setting}', [SettingController::class, 'update']);

            // Hapus data pelapor
            Route::delete('/admin/complainants/{complainant}', [ComplainantController::class, 'destroy']);
        });
    });
});

Route::get('/provinces', [WilayahController::class, 'getProvinces']);
Route::get('/cities/{province_code}', [WilayahController::class, 'getCities']);
Route::get('/districts/{city_code}', [WilayahController::class, 'getDistricts']);
Route::get('/villages/{district_code}', [WilayahController::class, 'getVillages']);

Route::apiResource('complaint-categories', ComplaintCategoryController::class);
// Endpoint legacy (bare array) yang masih dipakai frontend & pengujian lama.
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
