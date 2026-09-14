<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateComplaintRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Selaraskan penamaan ERD dengan nama kolom internal.
     */
    protected function prepareForValidation(): void
    {
        $mapped = [];

        if ($this->has('kategori_pelayanan_id') && ! $this->has('category_id')) {
            $mapped['category_id'] = $this->input('kategori_pelayanan_id');
        }

        if ($this->has('jenis_pengaduan_id') && ! $this->has('complaint_type_id')) {
            $mapped['complaint_type_id'] = $this->input('jenis_pengaduan_id');
        }

        if ($mapped !== []) {
            $this->merge($mapped);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'category_id' => 'sometimes|exists:complaint_categories,id',
            'complaint_type_id' => 'sometimes|nullable|exists:complaint_types,id',
            'assigned_to_user_id' => 'sometimes|nullable|exists:users,id',
            'description' => 'sometimes|string|min:20',
            'priority' => 'sometimes|in:low,medium,high,critical',
            'status' => 'sometimes|in:draft,submitted,reviewed,approved,resolved,closed',
            'deadline' => 'sometimes|nullable|date',

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
