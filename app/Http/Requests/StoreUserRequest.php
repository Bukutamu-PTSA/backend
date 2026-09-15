<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Normalisasi input dari form UI: "name" (Nama User) -> full_name,
     * dan bangun username dari email bila tidak dikirim.
     */
    protected function prepareForValidation(): void
    {
        $mapped = [];

        if ($this->has('name') && ! $this->has('full_name')) {
            $mapped['full_name'] = $this->input('name');
        }

        if (! $this->filled('username') && $this->filled('email')) {
            $mapped['username'] = str($this->input('email'))->before('@')->slug()->toString();
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
            'full_name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:50', Rule::unique('users', 'username')],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')],
            'password' => ['required', 'string', 'min:8'],
            'role' => ['required', Rule::in(['super_admin', 'admin', 'petugas'])],
            'status' => ['nullable', Rule::in(['aktif', 'nonaktif'])],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'full_name' => 'nama user',
        ];
    }
}
