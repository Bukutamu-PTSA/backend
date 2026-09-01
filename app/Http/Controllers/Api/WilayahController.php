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
        return response()->json(Province::orderBy('name')->pluck('name', 'code'));
    }

    public function getCities($provinceCode)
    {
        return response()->json(City::where('province_code', $provinceCode)->orderBy('name')->pluck('name', 'code'));
    }

    public function getDistricts($cityCode)
    {
        return response()->json(District::where('city_code', $cityCode)->orderBy('name')->pluck('name', 'code'));
    }

    public function getVillages($districtCode)
    {
        return response()->json(Village::where('district_code', $districtCode)->orderBy('name')->pluck('name', 'code'));
    }
}