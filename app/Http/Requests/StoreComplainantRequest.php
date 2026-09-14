<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreComplainantRequest extends FormRequest
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
            'nama_lengkap' => ['required', 'string', 'max:255'],
            'nik' => ['required', 'string', 'regex:/^[0-9]{16}$/', Rule::unique('complainants', 'nik')->ignore($complainantId)],
            'alamat' => ['required', 'string'],
            'jenis_kelamin' => ['required', 'in:laki-laki,perempuan'],
            'jabatan' => ['required', 'string', 'max:255'],
            'no_telp' => ['required', 'string', 'max:20'],
            'email' => ['required', 'email', 'max:255'],
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
