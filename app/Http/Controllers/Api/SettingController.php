<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AppSetting;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    public function index()
    {
        return response()->json([
            'success' => true,
            'data' => AppSetting::all(),
        ]);
    }

    public function update(Request $request, AppSetting $setting)
    {
        $validated = $request->validate([
            'setting_value' => 'required',
            'description' => 'nullable|string',
        ]);

        $setting->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Pengaturan diperbarui',
            'data' => $setting,
        ]);
    }
}
