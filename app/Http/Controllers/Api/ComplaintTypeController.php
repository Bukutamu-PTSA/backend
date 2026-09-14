<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ComplaintType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ComplaintTypeController extends Controller
{
    /**
     * Master data jenis pengaduan (Lisensi, SKP, Upah Kerja, K3, PHK, dst).
     */
    public function index(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => ComplaintType::orderBy('type_name')->get(['id', 'type_code', 'type_name', 'description']),
        ]);
    }

    /**
     * Simpan jenis pengaduan baru.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'type_code' => ['required', 'string', 'max:255', Rule::unique('complaint_types', 'type_code')],
            'type_name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
        ]);

        $type = ComplaintType::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Jenis pengaduan berhasil dibuat',
            'data' => $type,
        ], 201);
    }

    /**
     * Tampilkan detail jenis pengaduan.
     */
    public function show(ComplaintType $complaintType): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $complaintType,
        ]);
    }

    /**
     * Perbarui jenis pengaduan.
     */
    public function update(Request $request, ComplaintType $complaintType): JsonResponse
    {
        $validated = $request->validate([
            'type_code' => ['sometimes', 'required', 'string', 'max:255', Rule::unique('complaint_types', 'type_code')->ignore($complaintType)],
            'type_name' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
        ]);

        $complaintType->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Jenis pengaduan berhasil diperbarui',
            'data' => $complaintType,
        ]);
    }

    /**
     * Hapus jenis pengaduan apabila belum digunakan aduan.
     */
    public function destroy(ComplaintType $complaintType): JsonResponse
    {
        if ($complaintType->complaints()->withoutGlobalScopes()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Jenis pengaduan tidak dapat dihapus karena masih digunakan oleh aduan',
            ], 409);
        }

        $complaintType->delete();

        return response()->json([
            'success' => true,
            'message' => 'Jenis pengaduan dihapus',
        ]);
    }
}
