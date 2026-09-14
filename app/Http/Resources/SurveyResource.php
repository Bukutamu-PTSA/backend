<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SurveyResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'complaint_id' => $this->complaint_id,
            'survey_date' => $this->survey_date?->format('Y-m-d'),
            'nama_petugas' => $this->nama_petugas,
            'komunikasi_petugas' => $this->komunikasi_petugas,
            'penjelasan_materi' => $this->penjelasan_materi,
            'sarana_prasarana' => $this->sarana_prasarana,
            'catatan' => $this->catatan,
            'rating_overall' => $this->rating_overall,
            'is_anonymous' => (bool) $this->is_anonymous,
            'respondent_email' => $this->when(! $this->is_anonymous, $this->respondent_email),
            'respondent_phone' => $this->when(! $this->is_anonymous, $this->respondent_phone),
            'status' => $this->status,
            'answers' => $this->whenLoaded('responses', fn () => $this->responses->map(fn ($response) => [
                'survey_question_id' => $response->survey_question_id,
                'answer' => $response->answer,
            ])),
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
        ];
    }
}
