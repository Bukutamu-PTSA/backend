<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAttachmentRequest;
use App\Http\Resources\AttachmentResource;
use App\Models\Attachment;
use App\Models\Complaint;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AttachmentController extends Controller
{
    public function index(Complaint $complaint): JsonResponse
    {
        return AttachmentResource::collection($complaint->attachments()->latest()->get())
            ->response();
    }

    /**
     * Unggah satu atau beberapa berkas bukti pendukung (multipart).
     */
    public function store(StoreAttachmentRequest $request, Complaint $complaint): JsonResponse
    {
        $validated = $request->validated();

        $saved = collect($validated['attachments'])->map(fn ($file) => $complaint->addAttachment($file, [
            'description' => $validated['description'] ?? null,
            'attachment_category_id' => $validated['attachment_category_id'] ?? null,
            'uploaded_by_user_id' => $request->user()?->id,
        ]));

        return AttachmentResource::collection($saved)
            ->additional(['success' => true, 'message' => count($saved).' lampiran berhasil diunggah'])
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Unduh / streaming berkas bukti pendukung.
     */
    public function download(Complaint $complaint, Attachment $attachment): StreamedResponse
    {
        abort_unless($attachment->complaint_id === $complaint->id, 404);

        $disk = Storage::disk('public');
        abort_unless($disk->exists($attachment->file_path), 404, 'Berkas tidak ditemukan');

        return $disk->download($attachment->file_path, $attachment->file_name, [
            'Content-Type' => $attachment->mime_type ?: 'application/octet-stream',
        ]);
    }

    /**
     * Verifikasi validitas bukti oleh admin/pengawas.
     * PATCH /complaints/{complaint}/attachments/{attachment}/verify
     */
    public function verify(Request $request, Complaint $complaint, Attachment $attachment): JsonResponse
    {
        abort_unless($attachment->complaint_id === $complaint->id, 404);

        $validated = $request->validate([
            'is_verified' => ['required', 'boolean'],
        ]);

        $attachment->update(['is_verified' => $validated['is_verified']]);

        return (new AttachmentResource($attachment))
            ->additional([
                'success' => true,
                'message' => $validated['is_verified']
                    ? 'Bukti pendukung berhasil diverifikasi'
                    : 'Verifikasi bukti pendukung dibatalkan',
            ])
            ->response();
    }

    public function destroy(Complaint $complaint, Attachment $attachment): JsonResponse
    {
        abort_unless($attachment->complaint_id === $complaint->id, 404);

        Storage::disk('public')->delete($attachment->file_path);
        $attachment->delete();

        return response()->json(['success' => true, 'message' => 'Lampiran dihapus']);
    }
}
