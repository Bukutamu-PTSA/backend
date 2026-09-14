<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCompanyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $companyId = $this->route('company')?->id;

        return [
            'nama_perusahaan' => ['required', 'string', 'max:255', Rule::unique('companies', 'nama_perusahaan')->ignore($companyId)],
            'sector_id' => ['required', 'exists:industrial_sectors,id'],
            'size_id' => ['nullable', 'exists:company_sizes,id'],
            'alamat' => ['required', 'string'],
            'provinsi' => ['required', 'string', 'max:255'],
            'kota_kab' => ['required', 'string', 'max:255'],
            'kecamatan' => ['required', 'string', 'max:255'],
            'kelurahan' => ['required', 'string', 'max:255'],
            'no_telp' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:255'],
            'jumlah_naker' => ['required', 'integer', 'min:1'],
            // NPWP: 15 digit (format lama) atau 16 digit (format berbasis NIK),
            // menerima input dengan atau tanpa tanda titik/strip.
            'npwp' => ['nullable', 'string', 'regex:/^(\d{15,16}|\d{2}\.\d{3}\.\d{3}\.\d{1}-\d{3}\.\d{3})$/', Rule::unique('companies', 'npwp')->ignore($companyId)],
            'status' => ['nullable', 'in:aktif,nonaktif'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'npwp.regex' => 'Format NPWP tidak valid (gunakan 15/16 digit atau format XX.XXX.XXX.X-XXX.XXX).',
            'npwp.unique' => 'NPWP sudah terdaftar untuk perusahaan lain.',
            'sector_id.required' => 'Sektor industri wajib dipilih.',
        ];
    }
}
