<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreComplaintRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'category_id' => 'required|exists:complaint_categories,id',
            'description' => 'required|string',
            'attachments' => 'nullable|array',
            'attachments.*' => 'file|mimes:jpg,jpeg,png,webp,pdf|max:5120',

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
    public function messages(): array
    {
        return [
            'attachments.array' => 'Berkas bukti harus dikirim dalam bentuk daftar file (array). Gunakan attachments[] saat mengunggah.',
            'attachments.*.file' => 'Salah satu berkas bukti tidak valid. Pastikan file dikirim langsung melalui form-data, bukan dalam bentuk base64 atau teks.',
            'attachments.*.uploaded' => 'Salah satu berkas bukti gagal diunggah. Pastikan file berhasil dipilih, ukuran tidak melebihi 5 MB, dan koneksi stabil saat mengunggah.',
            'attachments.*.mimes' => 'Format berkas bukti tidak didukung. Hanya diperbolehkan: JPG, JPEG, PNG, WEBP, atau PDF.',
            'attachments.*.max' => 'Ukuran berkas bukti maksimal 5 MB.',
        ];
    }
}
