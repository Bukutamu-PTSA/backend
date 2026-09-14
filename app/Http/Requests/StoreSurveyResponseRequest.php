<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSurveyResponseRequest extends FormRequest
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
        return [
            'complaint_id' => ['nullable', 'exists:complaints,id'],
            'survey_date' => ['nullable', 'date'],
            'nama_petugas' => ['nullable', 'string', 'max:255'],
            'komunikasi_petugas' => ['nullable', 'in:baik,cukup,kurang'],
            'penjelasan_materi' => ['nullable', 'in:baik,cukup,kurang'],
            'sarana_prasarana' => ['nullable', 'in:baik,cukup,kurang'],
            'catatan' => ['nullable', 'string', 'max:2000'],
            'rating_overall' => ['required', 'integer', 'between:1,5'],
            'is_anonymous' => ['nullable', 'boolean'],

            // Kontak responden wajib bila survei tidak anonim.
            'respondent_email' => ['nullable', 'required_if:is_anonymous,false,0', 'email', 'max:255'],
            'respondent_phone' => ['nullable', 'string', 'max:20'],

            // Jawaban terhadap bank pertanyaan (opsional, dinamis).
            'answers' => ['nullable', 'array'],
            'answers.*.survey_question_id' => ['required_with:answers', 'integer', 'exists:survey_questions,id'],
            'answers.*.answer' => ['required_with:answers', 'string', 'max:1000'],
        ];
    }
}
