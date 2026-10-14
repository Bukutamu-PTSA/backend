<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $userId = $this->route('user')?->id ?? $this->route('id');

        return [
            'username' => 'sometimes|required|string|max:50|unique:users,username,'.$userId,
            'email' => 'sometimes|required|email|max:100|unique:users,email,'.$userId,
            'password' => 'sometimes|nullable|string|min:8',
            'role' => 'sometimes|required|in:super_admin,admin,pelapor',
        ];
    }
}
