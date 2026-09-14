<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FaqResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'question' => $this->question,
            'answer' => $this->answer,
            'category' => $this->category,
            'publication_status' => $this->publication_status,
            'order_number' => $this->order_number,
            'view_count' => $this->view_count,
            'approved_by' => $this->when($this->approved_by_user_id !== null || $this->relationLoaded('approvedBy'), fn () => [
                'id' => $this->approvedBy?->id,
                'username' => $this->approvedBy?->username,
                'full_name' => $this->approvedBy?->full_name,
            ]),
            'published_at' => $this->published_at?->format('Y-m-d H:i:s'),
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}
