<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ComplaintResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'ticket_number' => $this->ticket_number,
            'description' => $this->description,
            'status' => $this->status,
            'priority' => $this->priority,
            'complaint_date' => $this->complaint_date?->format('Y-m-d H:i:s'),
            'deadline' => $this->deadline?->format('Y-m-d H:i:s'),
            'closed_at' => $this->closed_at?->format('Y-m-d H:i:s'),
            'attachments' => $this->whenLoaded('attachments', fn () => AttachmentResource::collection($this->attachments)),
            'complainant' => [
                'id' => $this->complainant?->id,
                'nama_lengkap' => $this->complainant?->nama_lengkap,
                'nik' => $this->complainant?->nik,
                'alamat' => $this->complainant?->alamat,
                'jabatan' => $this->complainant?->jabatan,
                'jenis_kelamin' => $this->complainant?->jenis_kelamin,
                'no_telp' => $this->complainant?->no_telp,
                'email' => $this->complainant?->email,
            ],
            'company' => [
                'id' => $this->company?->id,
                'nama_perusahaan' => $this->company?->nama_perusahaan,
                'sector_id' => $this->company?->sector?->id,
                'size_id' => $this->company?->size?->id,
                'size' => [
                    'id' => $this->company?->size?->id,
                    'size_code' => $this->company?->size?->size_code,
                    'size_name' => $this->company?->size?->size_name,
                ],
                'sector' => [
                    'id' => $this->company?->sector?->id,
                    'sector_code' => $this->company?->sector?->sector_code,
                    'sector_name' => $this->company?->sector?->sector_name,
                ],
                'sektor_industri' => $this->company?->sector?->sector_name,
                'alamat' => $this->company?->alamat,
                'jumlah_naker' => $this->company?->jumlah_naker,
                'provinsi' => $this->company?->provinsi,
                'kota_kab' => $this->company?->kota_kab,
                'kecamatan' => $this->company?->kecamatan,
                'kelurahan' => $this->company?->kelurahan,
                'no_telp' => $this->company?->no_telp,
                'email' => $this->company?->email,
            ],
            'category' => [
                'id' => $this->category?->id,
                'category_code' => $this->category?->category_code,
                'category_name' => $this->category?->category_name,
                'description' => $this->category?->description,
            ],
            'complaint_type' => $this->when($this->complaint_type_id !== null || $this->relationLoaded('complaintType'), fn () => [
                'id' => $this->complaintType?->id,
                'type_code' => $this->complaintType?->type_code,
                'type_name' => $this->complaintType?->type_name,
            ]),
            'assigned_to' => $this->when($this->assigned_to_user_id !== null || $this->relationLoaded('assignedTo'), fn () => [
                'id' => $this->assignedTo?->id,
                'username' => $this->assignedTo?->username,
                'full_name' => $this->assignedTo?->full_name,
                'email' => $this->assignedTo?->email,
            ]),
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
        ];
    }
}
