<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ComplaintCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ComplaintCategoryController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(
            ComplaintCategory::orderBy('category_name')->get(['id', 'category_code', 'category_name'])
        );
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'category_code' => ['required', 'string', 'max:255', Rule::unique('complaint_categories', 'category_code')],
            'category_name' => ['required', 'string', 'max:255'],
        ]);

        $category = ComplaintCategory::create($validated);

        return response()->json([
            'data' => $category->only(['id', 'category_code', 'category_name']),
            'success' => true,
            'message' => 'Kategori aduan berhasil dibuat',
        ], 201);
    }

    public function show(ComplaintCategory $complaintCategory): JsonResponse
    {
        return response()->json([
            'data' => $complaintCategory->only(['id', 'category_code', 'category_name']),
        ]);
    }

    public function update(Request $request, ComplaintCategory $complaintCategory): JsonResponse
    {
        $validated = $request->validate([
            'category_code' => ['sometimes', 'required', 'string', 'max:255', Rule::unique('complaint_categories', 'category_code')->ignore($complaintCategory)],
            'category_name' => ['sometimes', 'required', 'string', 'max:255'],
        ]);

        $complaintCategory->update($validated);

        return response()->json([
            'data' => $complaintCategory->only(['id', 'category_code', 'category_name']),
            'success' => true,
            'message' => 'Kategori aduan berhasil diperbarui',
        ]);
    }

    public function destroy(ComplaintCategory $complaintCategory): JsonResponse
    {
        if ($complaintCategory->complaints()->withoutGlobalScopes()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Kategori tidak dapat dihapus karena masih digunakan oleh aduan',
            ], 409);
        }

        $complaintCategory->delete();

        return response()->json([
            'success' => true,
            'message' => 'Kategori aduan dihapus',
        ]);
    }
}
