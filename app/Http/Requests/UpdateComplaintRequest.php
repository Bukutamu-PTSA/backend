<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateComplaintRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'category_id' => 'sometimes|exists:complaint_categories,id',
            'description' => 'sometimes|string',

            'complainant.nama_lengkap' => 'sometimes|string|max:255',
            'complainant.nik' => ['sometimes', 'string', 'regex:/^[0-9]{16}$/'],
            'complainant.alamat' => 'sometimes|string',
            'complainant.jenis_kelamin' => 'sometimes|in:laki-laki,perempuan',
            'complainant.jabatan' => 'sometimes|string|max:255',
            'complainant.no_telp' => 'sometimes|string|max:20',
            'complainant.email' => 'sometimes|email|max:255',

            'company.nama_perusahaan' => 'sometimes|string|max:255',
            'company.sector_id' => 'sometimes|exists:industrial_sectors,id',
            'company.alamat' => 'sometimes|string',
            'company.jumlah_naker' => 'sometimes|integer|min:1',
            'company.provinsi' => 'sometimes|string|max:255',
            'company.kota_kab' => 'sometimes|string|max:255',
            'company.kecamatan' => 'sometimes|string|max:255',
            'company.kelurahan' => 'sometimes|string|max:255',
            'company.no_telp' => 'nullable|string|max:20',
            'company.email' => 'nullable|email|max:255',
        ];
    }
}
