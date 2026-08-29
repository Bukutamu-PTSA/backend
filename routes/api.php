<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\{
    AuthController,
    CategoryController,
    CompanySizeController,
    CompanyController,
    SettingController
};

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
