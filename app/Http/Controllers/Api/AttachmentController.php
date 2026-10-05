<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\AttachmentResource;
use App\Models\Attachment;
use App\Models\Complaint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AttachmentController extends Controller
{
    public function index(Complaint $complaint)
    {
        return AttachmentResource::collection($complaint->attachments()->latest()->get());
    }

    public function store(Complaint $complaint, Request $request)
    {
        $validated = $request->validate([
            'attachments' => 'required|array|min:1',
            'attachments.*' => 'file|mimes:jpg,jpeg,png,webp,pdf|max:5120',
        ]);

        $saved = collect($validated['attachments'])->map(fn ($file) => $complaint->addAttachment($file));

        return AttachmentResource::collection($saved)
            ->additional(['success' => true, 'message' => count($saved).' lampiran berhasil diunggah'])
            ->response()
            ->setStatusCode(201);
    }

    public function destroy(Complaint $complaint, Attachment $attachment)
    {
        abort_unless($attachment->complaint_id === $complaint->id, 404);

        Storage::disk('public')->delete($attachment->file_path);
        $attachment->delete();

        return response()->json(['success' => true, 'message' => 'Lampiran dihapus']);
    }
}
