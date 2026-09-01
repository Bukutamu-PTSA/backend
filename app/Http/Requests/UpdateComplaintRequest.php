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
            'description' => 'sometimes|string|min:20',
            'status' => 'sometimes|in:DIPROSES,SELESAI',
            'notes' => 'nullable|string',
            'follow_up_date' => 'nullable|date',
            'assigned_to' => 'nullable|exists:admin_profiles,id',
            'internal_notes' => 'nullable|string',
        ];
    }
}
