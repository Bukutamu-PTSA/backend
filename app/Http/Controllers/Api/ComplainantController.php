<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreComplainantRequest;
use App\Http\Requests\UpdateComplainantRequest;
use App\Models\Complainant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ComplainantController extends Controller
{
    /**
     * Daftar pelapor. Mendukung pencarian berdasarkan NIK atau nama.
     * Contoh: GET /complainants?nik=3201010101010001
     */
    public function index(Request $request): JsonResponse
    {
        $query = Complainant::query();

        if ($request->filled('nik')) {
            $query->where('nik', $request->string('nik'));
        }

        if ($request->filled('search')) {
            $term = '%'.mb_strtolower((string) $request->string('search')).'%';
            $query->where(function ($inner) use ($term) {
                $inner->whereRaw('LOWER(nama_lengkap) LIKE ?', [$term])
                    ->orWhere('nik', 'like', $term);
            });
        }

        return response()->json([
            'success' => true,
            'data' => $query->orderBy('nama_lengkap')->paginate(20),
        ]);
    }

    /**
     * Simpan biodata pelapor baru.
     */
    public function store(StoreComplainantRequest $request): JsonResponse
    {
        $complainant = Complainant::create($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Data pelapor berhasil ditambahkan',
            'data' => $complainant,
        ], 201);
    }

    /**
     * Tampilkan detail satu pelapor.
     */
    public function show(Complainant $complainant): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $complainant,
        ]);
    }

    /**
     * Perbarui biodata pelapor.
     */
    public function update(UpdateComplainantRequest $request, Complainant $complainant): JsonResponse
    {
        $complainant->update($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Data pelapor berhasil diperbarui',
            'data' => $complainant,
        ]);
    }

    /**
     * Hapus data pelapor apabila belum terikat pada tiket aduan.
     */
    public function destroy(Complainant $complainant): JsonResponse
    {
        if ($complainant->complaints()->withoutGlobalScopes()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Pelapor tidak dapat dihapus karena masih memiliki aduan',
            ], 409);
        }

        $complainant->delete();

        return response()->json([
            'success' => true,
            'message' => 'Data pelapor dihapus',
        ]);
    }
}
