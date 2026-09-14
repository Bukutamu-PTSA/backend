<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreComplaintRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Selaraskan penamaan ERD (kategori_pelayanan_id / jenis_pengaduan_id)
     * dengan nama kolom internal (category_id / complaint_type_id).
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
            // KATEGORI_PELAYANAN (wajib)
            'category_id' => 'required|exists:complaint_categories,id',
            // JENIS_PENGADUAN (opsional)
            'complaint_type_id' => 'nullable|exists:complaint_types,id',
            // Petugas pemeriksa (opsional, FK ke users)
            'assigned_to_user_id' => 'nullable|exists:users,id',
            'description' => 'required|string',
            'priority' => 'nullable|in:low,medium,high,critical',
            'status' => 'nullable|in:draft,submitted,reviewed,approved,resolved,closed',
            'complaint_date' => 'nullable|date',
            'deadline' => 'nullable|date|after_or_equal:complaint_date',

            'attachments' => 'nullable|array',
            'attachments.*' => 'image|mimes:jpg,jpeg,png,webp|max:5120',

            'complainant.nama_lengkap' => 'required|string|max:255',
            'complainant.nik' => ['required', 'string', 'regex:/^[0-9]{16}$/'],
            'complainant.alamat' => 'required|string',
            'complainant.jenis_kelamin' => 'required|in:laki-laki,perempuan',
            'complainant.jabatan' => 'required|string|max:255',
            'complainant.no_telp' => 'required|string|max:20',
            'complainant.email' => 'required|email|max:255',

            'company.nama_perusahaan' => 'required|string|max:255',
            'company.sector_id' => 'required|exists:industrial_sectors,id',
            'company.alamat' => 'required|string',
            'company.jumlah_naker' => 'required|integer|min:1',
            'company.provinsi' => 'required|string|max:255',
            'company.kota_kab' => 'required|string|max:255',
            'company.kecamatan' => 'required|string|max:255',
            'company.kelurahan' => 'required|string|max:255',
            'company.no_telp' => 'nullable|string|max:20',
            'company.email' => 'nullable|email|max:255',
        ];
    }
}
