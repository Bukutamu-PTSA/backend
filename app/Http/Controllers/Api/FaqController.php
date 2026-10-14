<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Faq;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FaqController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => Faq::orderBy('id')->get(['id', 'question', 'answer']),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'question' => ['required', 'string', 'max:1000'],
            'answer' => ['required', 'string', 'max:5000'],
        ]);

        $faq = Faq::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'FAQ berhasil dibuat',
            'data' => $faq->only(['id', 'question', 'answer']),
        ], 201);
    }

    public function show(Faq $faq): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'FAQ berhasil diambil',
            'data' => $faq->only(['id', 'question', 'answer']),
        ]);
    }

    public function update(Request $request, Faq $faq): JsonResponse
    {
        $validated = $request->validate([
            'question' => ['sometimes', 'required', 'string', 'max:1000'],
            'answer' => ['sometimes', 'required', 'string', 'max:5000'],
        ]);

        $faq->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'FAQ berhasil diperbarui',
            'data' => $faq->only(['id', 'question', 'answer']),
        ]);
    }

    public function destroy(Faq $faq): JsonResponse
    {
        $faq->delete();

        return response()->json([
            'success' => true,
            'message' => 'FAQ dihapus',
        ]);
    }
}
