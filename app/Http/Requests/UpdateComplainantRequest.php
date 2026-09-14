<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateComplainantRequest extends FormRequest
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
        $complainantId = $this->route('complainant')?->id;

        return [
            'nama_lengkap' => ['sometimes', 'required', 'string', 'max:255'],
            'nik' => ['sometimes', 'required', 'string', 'regex:/^[0-9]{16}$/', Rule::unique('complainants', 'nik')->ignore($complainantId)],
            'alamat' => ['sometimes', 'required', 'string'],
            'jenis_kelamin' => ['sometimes', 'required', 'in:laki-laki,perempuan'],
            'jabatan' => ['sometimes', 'required', 'string', 'max:255'],
            'no_telp' => ['sometimes', 'required', 'string', 'max:20'],
            'email' => ['sometimes', 'required', 'email', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'nik.regex' => 'NIK harus terdiri dari 16 digit angka.',
            'nik.unique' => 'NIK sudah terdaftar sebagai pelapor.',
        ];
    }
}
