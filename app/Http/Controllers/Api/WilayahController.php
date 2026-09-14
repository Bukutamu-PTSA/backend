<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Laravolt\Indonesia\Models\City;
use Laravolt\Indonesia\Models\District;
use Laravolt\Indonesia\Models\Province;
use Laravolt\Indonesia\Models\Village;

class WilayahController extends Controller
{
    public function getProvinces()
    {
        return response()->json([
            'data' => Province::orderBy('name')->pluck('name', 'code'),
            'success' => true,
            'message' => 'Data provinsi berhasil diambil',
        ]);
    }

    public function getCities($provinceCode)
    {
        $cities = City::where('province_code', $provinceCode)->orderBy('name')->pluck('name', 'code');

        if ($cities->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => "Provinsi dengan kode '{$provinceCode}' tidak ditemukan",
            ], 404);
        }

        return response()->json([
            'data' => $cities,
            'success' => true,
            'message' => 'Data kota berhasil diambil',
        ]);
    }

    public function getDistricts($cityCode)
    {
        $districts = District::where('city_code', $cityCode)->orderBy('name')->pluck('name', 'code');

        if ($districts->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => "Kota/Kabupaten dengan kode '{$cityCode}' tidak ditemukan",
            ], 404);
        }

        return response()->json([
            'data' => $districts,
            'success' => true,
            'message' => 'Data kecamatan berhasil diambil',
        ]);
    }

    public function getVillages($districtCode)
    {
        $villages = Village::where('district_code', $districtCode)->orderBy('name')->pluck('name', 'code');

        if ($villages->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => "Kecamatan dengan kode '{$districtCode}' tidak ditemukan",
            ], 404);
        }

        return response()->json([
            'data' => $villages,
            'success' => true,
            'message' => 'Data kelurahan berhasil diambil',
        ]);
    }
}
