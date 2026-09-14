<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCompanyRequest;
use App\Models\Company;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CompanyController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Company::with(['size', 'sector']);

        if ($request->filled('search')) {
            $query->where('nama_perusahaan', 'like', '%'.$request->string('search').'%');
        }

        return response()->json([
            'success' => true,
            'data' => $query->paginate(20),
        ]);
    }

    public function store(StoreCompanyRequest $request): JsonResponse
    {
        $company = Company::create($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Data perusahaan ditambahkan',
            'data' => $company->load(['size', 'sector']),
        ], 201);
    }
}
