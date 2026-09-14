<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreFaqRequest extends FormRequest
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
        $required = $this->isMethod('post') ? 'required' : 'sometimes';

        return [
            'question' => [$required, 'string', 'max:500'],
            'answer' => [$required, 'string'],
            'category' => ['nullable', 'string', 'max:255'],
            'publication_status' => ['nullable', 'in:published,draft'],
            'order_number' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
