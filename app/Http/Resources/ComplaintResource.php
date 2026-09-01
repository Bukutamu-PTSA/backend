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
            'follow_up_date' => $this->follow_up_date?->format('Y-m-d H:i:s'),
            'internal_notes' => $this->when($request->user()?->isAdmin(), $this->internal_notes),
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
            'assigned_to' => $this->when($this->assignedAdmin, [
                'id' => $this->assignedAdmin?->id,
                'nama' => $this->assignedAdmin?->nama_lengkap,
            ]),
            'updates' => $this->whenLoaded('updates', fn () => UpdateResource::collection($this->updates)),
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
        ];
    }
}
