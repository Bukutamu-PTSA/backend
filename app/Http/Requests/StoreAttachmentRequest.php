<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreAttachmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Validasi berkas bukti pendukung sesuai ERD BUKTI_PENDUKUNG.
     * Tipe file yang diizinkan: JPG, JPEG, PNG, PDF, MP4. Maks 20 MB/berkas.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'attachments' => ['required', 'array', 'min:1'],
            'attachments.*' => ['file', 'mimes:jpg,jpeg,png,pdf,mp4', 'max:20480'],
            'description' => ['nullable', 'string', 'max:500'],
            'attachment_category_id' => ['nullable', 'exists:attachment_categories,id'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'attachments.*.mimes' => 'Tipe berkas harus salah satu dari: jpg, jpeg, png, pdf, mp4.',
            'attachments.*.max' => 'Ukuran setiap berkas maksimal 20 MB.',
        ];
    }
}
