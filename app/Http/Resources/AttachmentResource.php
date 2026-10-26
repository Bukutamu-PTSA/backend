<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\URL;

class AttachmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'file_name' => $this->file_name,
            'file_type' => $this->file_type,
            'file_size' => $this->file_size,
            'mime_type' => $this->mime_type,
            // File disimpan di disk private ("local"), jadi tidak bisa diakses lewat
            // /storage/... Gunakan signed URL relatif (/api/...) agar ikut routing /api
            // di reverse proxy dan tidak bergantung pada APP_URL.
            'url' => URL::temporarySignedRoute(
                'complaints.attachments.file',
                now()->addHour(),
                ['complaint' => $this->complaint_id, 'attachment' => $this->id],
                absolute: false,
            ),
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
        ];
    }
}
