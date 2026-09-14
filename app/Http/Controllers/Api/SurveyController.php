<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSurveyResponseRequest;
use App\Http\Resources\SurveyResource;
use App\Models\Survey;
use App\Models\SurveyQuestion;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class SurveyController extends Controller
{
    /**
     * Daftar instrumen pertanyaan survei aktif untuk ditampilkan ke responden.
     */
    public function questions(): JsonResponse
    {
        $questions = SurveyQuestion::query()
            ->orderBy('order_number')
            ->orderBy('id')
            ->get(['id', 'question_text', 'answer_type', 'option_a', 'option_b', 'option_c', 'is_required', 'order_number']);

        return response()->json([
            'success' => true,
            'data' => $questions,
        ]);
    }

    /**
     * Submit jawaban kuesioner kepuasan layanan.
     */
    public function store(StoreSurveyResponseRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $isAnonymous = (bool) ($validated['is_anonymous'] ?? false);

        $survey = DB::transaction(function () use ($validated, $isAnonymous) {
            $survey = Survey::create([
                'complaint_id' => $validated['complaint_id'] ?? null,
                'survey_date' => $validated['survey_date'] ?? now()->toDateString(),
                'nama_petugas' => $validated['nama_petugas'] ?? null,
                'komunikasi_petugas' => $validated['komunikasi_petugas'] ?? null,
                'penjelasan_materi' => $validated['penjelasan_materi'] ?? null,
                'sarana_prasarana' => $validated['sarana_prasarana'] ?? null,
                'catatan' => $validated['catatan'] ?? null,
                'rating_overall' => $validated['rating_overall'],
                'is_anonymous' => $isAnonymous,
                'respondent_email' => $isAnonymous ? null : ($validated['respondent_email'] ?? null),
                'respondent_phone' => $isAnonymous ? null : ($validated['respondent_phone'] ?? null),
                'status' => 'submitted',
            ]);

            foreach ($validated['answers'] ?? [] as $answer) {
                $survey->responses()->create([
                    'survey_question_id' => $answer['survey_question_id'],
                    'answer' => $answer['answer'],
                ]);
            }

            return $survey;
        });

        return (new SurveyResource($survey->load('responses')))
            ->additional(['success' => true, 'message' => 'Terima kasih, survei kepuasan berhasil dikirim'])
            ->response()
            ->setStatusCode(201);
    }
}
