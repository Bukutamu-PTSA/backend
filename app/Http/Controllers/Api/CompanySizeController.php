<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CompanySize;
use Illuminate\Http\Request;

class CompanySizeController extends Controller
{
    public function index()
    {
        return response()->json([
            'success' => true,
            'data' => CompanySize::all(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'size_code' => 'required|string|unique:company_sizes,size_code',
            'size_name' => 'required|string',
            'min_employees' => 'required|integer|min:0',
            'max_employees' => 'nullable|integer|gt:min_employees',
        ]);

        $size = CompanySize::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Skala berhasil dibuat',
            'data' => $size,
        ], 201);
    }
}
