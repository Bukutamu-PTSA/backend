<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Complainant;
use Illuminate\Http\Request;

class ComplainantController extends Controller
{
    public function index(Request $request)
    {
        $query = Complainant::query()->latest('id');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(fn ($q) => $q->where('nama_lengkap', 'like', "%{$search}%")
                ->orWhere('nik', 'like', "%{$search}%"));
        }

        return response()->json([
            'success' => true,
            'data' => $query->paginate(20),
        ]);
    }
}
