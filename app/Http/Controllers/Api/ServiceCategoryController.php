<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ComplaintCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ServiceCategoryController extends Controller
{
    /**
     * Master data kategori pelayanan (Wajib Lapor, Hubungan Kerja, K3, dst).
     */
    public function index(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => ComplaintCategory::orderBy('category_name')->get(),
        ]);
    }

    /**
     * Simpan kategori pelayanan baru.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'category_code' => ['required', 'string', 'max:255', Rule::unique('complaint_categories', 'category_code')],
            'category_name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'icon' => ['nullable', 'string', 'max:255'],
            'color' => ['nullable', 'string', 'max:20'],
            'status' => ['nullable', 'in:aktif,nonaktif'],
        ]);

        $category = ComplaintCategory::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Kategori pelayanan berhasil dibuat',
            'data' => $category,
        ], 201);
    }

    /**
     * Tampilkan detail kategori pelayanan.
     */
    public function show(ComplaintCategory $serviceCategory): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $serviceCategory,
        ]);
    }

    /**
     * Perbarui kategori pelayanan.
     */
    public function update(Request $request, ComplaintCategory $serviceCategory): JsonResponse
    {
        $validated = $request->validate([
            'category_code' => ['sometimes', 'required', 'string', 'max:255', Rule::unique('complaint_categories', 'category_code')->ignore($serviceCategory)],
            'category_name' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'icon' => ['nullable', 'string', 'max:255'],
            'color' => ['nullable', 'string', 'max:20'],
            'status' => ['nullable', 'in:aktif,nonaktif'],
        ]);

        $serviceCategory->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Kategori pelayanan berhasil diperbarui',
            'data' => $serviceCategory,
        ]);
    }

    /**
     * Hapus kategori pelayanan apabila belum digunakan aduan.
     */
    public function destroy(ComplaintCategory $serviceCategory): JsonResponse
    {
        if ($serviceCategory->complaints()->withoutGlobalScopes()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Kategori tidak dapat dihapus karena masih digunakan oleh aduan',
            ], 409);
        }

        $serviceCategory->delete();

        return response()->json([
            'success' => true,
            'message' => 'Kategori pelayanan dihapus',
        ]);
    }
}
