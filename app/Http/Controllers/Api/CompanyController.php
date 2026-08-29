<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Company;
use Illuminate\Http\Request;

class CompanyController extends Controller
{
    public function index(Request $request)
    {
        $query = Company::with('size');

        if ($request->filled('search')) {
            $query->where('nama_perusahaan', 'like', '%' . $request->search . '%');
        }

        return response()->json([
            'success' => true,
            'data' => $query->paginate(20),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nama_perusahaan' => 'required|string|max:255',
            'sektor_industri' => 'required|string|max:100',
            'alamat' => 'required|string',
            'jumlah_naker' => 'required|integer|min:1',
            'size_id' => 'required|exists:company_sizes,id',
            'provinsi' => 'required|string',
            'kota_kab' => 'required|string',
            'kecamatan' => 'required|string',
            'kelurahan' => 'required|string',
            'no_telp' => 'nullable|string',
            'email' => 'nullable|email',
        ]);

        $company = Company::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Data perusahaan ditambahkan',
            'data' => $company,
        ], 201);
    }
}
