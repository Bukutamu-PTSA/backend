<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\IndustrialSector;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class IndustrySectorController extends Controller
{
    /**
     * Master data sektor industri.
     */
    public function index(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => IndustrialSector::orderBy('sector_name')->get(['id', 'sector_code', 'sector_name', 'description']),
        ]);
    }

    /**
     * Simpan sektor industri baru.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'sector_code' => ['required', 'string', 'max:255', Rule::unique('industrial_sectors', 'sector_code')],
            'sector_name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
        ]);

        $sector = IndustrialSector::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Sektor industri berhasil dibuat',
            'data' => $sector,
        ], 201);
    }

    /**
     * Perbarui sektor industri.
     */
    public function update(Request $request, IndustrialSector $industrialSector): JsonResponse
    {
        $validated = $request->validate([
            'sector_code' => ['sometimes', 'required', 'string', 'max:255', Rule::unique('industrial_sectors', 'sector_code')->ignore($industrialSector)],
            'sector_name' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
        ]);

        $industrialSector->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Sektor industri berhasil diperbarui',
            'data' => $industrialSector,
        ]);
    }

    /**
     * Hapus sektor industri apabila belum digunakan perusahaan.
     */
    public function destroy(IndustrialSector $industrialSector): JsonResponse
    {
        if ($industrialSector->companies()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Sektor tidak dapat dihapus karena masih digunakan oleh perusahaan',
            ], 409);
        }

        $industrialSector->delete();

        return response()->json([
            'success' => true,
            'message' => 'Sektor industri dihapus',
        ]);
    }
}
