<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class AttachmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'complaint_id' => $this->complaint_id,
            'file_name' => $this->file_name,
            'file_type' => $this->file_type,
            'file_size' => $this->file_size,
            'mime_type' => $this->mime_type,
            'description' => $this->description,
            'is_verified' => (bool) $this->is_verified,
            'uploaded_by' => $this->when($this->uploaded_by_user_id !== null || $this->relationLoaded('uploadedBy'), fn () => [
                'id' => $this->uploadedBy?->id,
                'username' => $this->uploadedBy?->username,
                'full_name' => $this->uploadedBy?->full_name,
            ]),
            'url' => Storage::url($this->file_path),
            'download_url' => route('attachments.download', [
                'complaint' => $this->complaint_id,
                'attachment' => $this->id,
            ]),
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
        ];
    }
}
