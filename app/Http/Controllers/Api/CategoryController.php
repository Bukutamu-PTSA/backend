<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ComplaintCategory;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function index()
    {
        return response()->json([
            'success' => true,
            'data' => ComplaintCategory::all(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'category_code' => 'required|string|unique:complaint_categories,category_code',
            'category_name' => 'required|string|max:100',
            'description' => 'nullable|string',
        ]);

        $category = ComplaintCategory::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Kategori berhasil disimpan',
            'data' => $category,
        ], 201);
    }
}
