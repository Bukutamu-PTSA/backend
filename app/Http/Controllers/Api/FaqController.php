<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreFaqRequest;
use App\Http\Resources\FaqResource;
use App\Models\Faq;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FaqController extends Controller
{
    /**
     * Daftar FAQ publik — hanya menampilkan yang berstatus "published".
     */
    public function index(Request $request): JsonResponse
    {
        $faqs = Faq::query()
            ->where('publication_status', 'published')
            ->when($request->filled('category'), fn ($query) => $query->where('category', $request->string('category')))
            ->when($request->filled('search'), function ($query) use ($request) {
                $term = '%'.mb_strtolower((string) $request->string('search')).'%';
                $query->where(function ($inner) use ($term) {
                    $inner->whereRaw('LOWER(question) LIKE ?', [$term])
                        ->orWhereRaw('LOWER(answer) LIKE ?', [$term]);
                });
            })
            ->orderBy('order_number')
            ->orderByDesc('published_at')
            ->get();

        return FaqResource::collection($faqs)->response();
    }

    /**
     * Detail FAQ publik. Menambah view_count setiap diakses.
     */
    public function show(Faq $faq): JsonResponse
    {
        abort_unless($faq->publication_status === 'published', 404);

        $faq->increment('view_count');

        return (new FaqResource($faq->refresh()))->response();
    }

    /**
     * Daftar seluruh FAQ untuk manajemen admin (termasuk draft).
     */
    public function adminIndex(Request $request): JsonResponse
    {
        $faqs = Faq::query()
            ->with('approvedBy')
            ->when($request->filled('status'), fn ($query) => $query->where('publication_status', $request->string('status')))
            ->orderBy('order_number')
            ->latest('id')
            ->paginate(20);

        return FaqResource::collection($faqs)->response();
    }

    public function store(StoreFaqRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['publication_status'] ??= 'draft';

        if ($data['publication_status'] === 'published') {
            $data['approved_by_user_id'] = $request->user()?->id;
            $data['published_at'] = now();
        }

        $faq = Faq::create($data);

        return (new FaqResource($faq))
            ->additional(['success' => true, 'message' => 'FAQ berhasil dibuat'])
            ->response()
            ->setStatusCode(201);
    }

    public function update(StoreFaqRequest $request, Faq $faq): JsonResponse
    {
        $data = $request->validated();

        // Bila status diubah menjadi published dan belum pernah diterbitkan,
        // catat waktu publikasi dan penyetujunya.
        if (($data['publication_status'] ?? null) === 'published' && $faq->published_at === null) {
            $data['approved_by_user_id'] = $request->user()?->id;
            $data['published_at'] = now();
        }

        $faq->update($data);

        return (new FaqResource($faq->refresh()))
            ->additional(['success' => true, 'message' => 'FAQ berhasil diperbarui'])
            ->response();
    }

    /**
     * Setujui & terbitkan FAQ (Approve).
     */
    public function approve(Request $request, Faq $faq): JsonResponse
    {
        $faq->update([
            'publication_status' => 'published',
            'approved_by_user_id' => $request->user()?->id,
            'published_at' => $faq->published_at ?? now(),
        ]);

        return (new FaqResource($faq->refresh()))
            ->additional(['success' => true, 'message' => 'FAQ berhasil disetujui dan dipublikasikan'])
            ->response();
    }

    public function destroy(Faq $faq): JsonResponse
    {
        $faq->delete();

        return response()->json(['success' => true, 'message' => 'FAQ dihapus']);
    }
}
