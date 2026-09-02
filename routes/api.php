<?php

use App\Http\Controllers\Api\AttachmentController;
use App\Http\Controllers\Api\ComplaintController;
use App\Http\Controllers\Api\WilayahController;
use App\Models\ComplaintCategory;
use App\Models\IndustrialSector;
use Illuminate\Support\Facades\Route;

Route::get('/provinces', [WilayahController::class, 'getProvinces']);
Route::get('/cities/{province_code}', [WilayahController::class, 'getCities']);
Route::get('/districts/{city_code}', [WilayahController::class, 'getDistricts']);
Route::get('/villages/{district_code}', [WilayahController::class, 'getVillages']);

Route::get('complaint-categories', fn () => ComplaintCategory::orderBy('category_name')->get(['id', 'category_code', 'category_name']));
Route::get('industrial-sectors', fn () => IndustrialSector::orderBy('id')->get(['id', 'sector_code', 'sector_name']));

Route::post('complaints', [ComplaintController::class, 'store']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('dashboard/complaint-summary', [ComplaintController::class, 'summary']);
    Route::get('dashboard/statistics', [ComplaintController::class, 'statistics']);
    Route::get('complaints/{complaint}/pdf', [ComplaintController::class, 'pdf']);
    Route::apiResource('complaints', ComplaintController::class)->except('store');
    Route::get('complaints/{complaint}/attachments', [AttachmentController::class, 'index']);
    Route::post('complaints/{complaint}/attachments', [AttachmentController::class, 'store']);
    Route::delete('complaints/{complaint}/attachments/{attachment}', [AttachmentController::class, 'destroy']);
});
