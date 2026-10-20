<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\AttachmentResource;
use App\Models\Attachment;
use App\Models\Complaint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;

class AttachmentController extends Controller
{
    public function index(Complaint $complaint)
    {
        return AttachmentResource::collection($complaint->attachments()->latest()->get());
    }

    public function store(Complaint $complaint, Request $request)
    {
        $validated = $request->validate([
            'attachments'             => ['nullable', 'array', 'max:5'],
            'attachments.*.file'      => [
                'required',   // kalau item attachments dikirim, file-nya wajib ada
                'file',
                'mimes:jpg,jpeg,png,webp,pdf',
                'mimetypes:image/jpeg,image/png,image/webp,application/pdf',
                'max:5120',
            ],
            'attachments.*.file_type' => ['nullable', 'string', 'in:photo,document'],
        ]);

        $items = $validated['attachments'] ?? [];

        foreach ($items as $index => $item) {
            if (!$this->validateFileSignature($item['file'])) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    "attachments.$index.file" => ['Format atau signature file tidak valid.'],
                ]);
            }
        }

        $saved = collect($items)
            ->map(fn($item) => $complaint->addAttachment($item['file'], $item['file_type'] ?? null));
        return AttachmentResource::collection($saved)
            ->additional([
                'success' => true,
                'message' => count($saved) . ' lampiran berhasil diunggah',
            ])
            ->response()
            ->setStatusCode(201);
    }
    public function destroy(Complaint $complaint, Attachment $attachment)
    {
        abort_unless($attachment->complaint_id === $complaint->id, 404);

        Storage::disk('local')->delete($attachment->file_path);
        $attachment->delete();

        return response()->json(['success' => true, 'message' => 'Lampiran dihapus']);
    }
    private function validateFileSignature(UploadedFile $file): bool
    {
        $mimeType = $file->getMimeType();

        $signatures = [
            'application/pdf' => '%PDF-',
            'image/jpeg' => "\xFF\xD8\xFF",
            'image/png' => "\x89PNG\r\n\x1A\n",
        ];

        $handle = fopen($file->getRealPath(), 'rb');

        if (!$handle) {
            return false;
        }

        $header = fread($handle, 12);

        fclose($handle);

        // Validasi khusus WebP
        if ($mimeType === 'image/webp') {
            return strlen($header) >= 12
                && substr($header, 0, 4) === 'RIFF'
                && substr($header, 8, 4) === 'WEBP';
        }

        $signature = $signatures[$mimeType] ?? null;

        if ($signature === null) {
            return false;
        }

        return str_starts_with($header, $signature);
    }
}
