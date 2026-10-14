<?php

namespace App\Http\Controllers\Api;

use App\Models\Survey;
use App\Models\SurveyQuestion;
use App\Models\SurveyResponse;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class SurveyController
{
    /**
     * Skor skala 5 untuk jawaban likert survei.
     *
     * @var array<string, int>
     */
    private const SATISFACTION_SCORES = [
        'Baik' => 5,
        'Cukup' => 3,
        'Kurang' => 1,
    ];

    /**
     * Mendapatkan form survei publik tunggal. Dibuat otomatis bila belum ada.
     * GET /api/surveys/form
     */
    public function form(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->theSurvey()->load('questions'),
        ], 200);
    }

    /**
     * Menyimpan jawaban ke form survei publik (tidak membuat sesi baru).
     * POST /api/surveys
     */
    public function submit(Request $request): JsonResponse
    {
        $survey = $this->theSurvey();

        if ($survey->status === 'CLOSED') {
            return response()->json([
                'success' => false,
                'message' => 'Survey is closed and cannot accept responses',
            ], 422);
        }

        $validator = Validator::make($request->all(), [
            'responses' => 'required|array',
            'responses.*.question_number' => 'required|integer',
            'responses.*.answer' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $submissionId = (string) Str::uuid();

            $responses = DB::transaction(function () use ($request, $survey, $submissionId) {
                $questions = $survey->questions()->get()->keyBy('question_number');
                $answeredQuestions = [];
                $storedResponses = [];

                foreach ($request->responses as $response) {
                    $number = (int) $response['question_number'];
                    $answer = $response['answer'];

                    if (! isset($questions[$number])) {
                        throw new \Exception("Question number {$number} is not part of the survey");
                    }

                    $question = $questions[$number];
                    $answeredQuestions[$number] = true;

                    if ($question->type === 'likert' && ! in_array($answer, $question->options ?? [])) {
                        throw new \Exception('Question '.$number.': Answer must be one of: '.implode(', ', $question->options));
                    }

                    $storedResponses[] = SurveyResponse::create([
                        'survey_id' => $survey->id,
                        'survey_question_id' => $question->id,
                        'answer' => $answer,
                        'submission_id' => $submissionId,
                    ]);
                }

                for ($i = 1; $i <= 4; $i++) {
                    if (! isset($answeredQuestions[$i])) {
                        throw new \Exception("Question {$i} is required");
                    }
                }

                return $storedResponses;
            });

            return response()->json([
                'success' => true,
                'message' => 'Responses stored successfully',
                'data' => $responses,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Get survey details
     * GET /api/surveys/{survey}
     */
    public function show(Survey $survey): JsonResponse
    {
        try {
            $survey->load('questions');

            return response()->json([
                'success' => true,
                'data' => $survey,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Survey not found',
            ], 404);
        }
    }

    /**
     * Survey satisfaction index
     * GET /api/surveys/satisfaction
     */
    public function satisfaction(Request $request): JsonResponse
    {
        $period = $request->validate([
            'period' => 'nullable|in:today',
        ])['period'] ?? null;

        $formId = Survey::query()->orderBy('id')->value('id');

        $scores = SurveyResponse::query()
            ->whereHas('question', fn ($query) => $query->where('type', 'likert'))
            ->whereIn('answer', array_keys(self::SATISFACTION_SCORES))
            ->when(
                $formId,
                fn ($query) => $query->where('survey_id', $formId),
            )
            ->when(
                $period === 'today',
                fn ($query) => $query->whereDate('created_at', today()),
            )
            ->pluck('answer')
            ->map(fn (string $answer) => self::SATISFACTION_SCORES[$answer]);

        return response()->json([
            'success' => true,
            'data' => [
                'satisfaction_index' => $scores->isEmpty() ? null : round($scores->avg(), 2),
                'satisfaction_scale' => 5,
                'satisfaction_responses' => $scores->count(),
            ],
            'meta' => [
                'period' => $period ?? 'all',
            ],
        ]);
    }

    /**
     * List all survey responses for the dashboard (grouped per submission)
     * GET /api/surveys/responses
     */
    public function responses(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->responsesPayload(),
        ], 200);
    }

    /**
     * Unduh rekap jawaban survei sebagai PDF.
     * GET /api/surveys/responses/pdf
     */
    public function pdf()
    {
        $field = fn ($value) => htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');

        $data = $this->responsesPayload();
        $questions = $data['questions'];

        $head = '<th class="num">No</th><th class="time">Waktu</th>';
        foreach ($questions as $question) {
            $head .= '<th>'.$field($question['question_number'].'. '.$question['question_text']).'</th>';
        }

        $rows = '';
        foreach ($data['submissions'] as $submission) {
            $rows .= '<tr>
            <td class="num">'.$field($submission['no']).'</td>
            <td class="time">'.$field(optional($submission['submitted_at'])->format('d M Y H:i')).'</td>';

            foreach ($questions as $question) {
                $rows .= '<td>'.$field($submission['answers'][$question['question_number']] ?? '-').'</td>';
            }

            $rows .= '</tr>';
        }

        if ($rows === '') {
            $rows = '<tr><td colspan="'.(count($questions) + 2).'" class="empty">Belum ada jawaban survei.</td></tr>';
        }

        $html = '<!doctype html><html><head><meta charset="UTF-8"><style>
    @page { margin: 15mm; }
    body { font-family: DejaVu Sans, sans-serif; font-size: 10.5pt; color: #1f2d3d; line-height: 1.45; }
    h1 { font-size: 15pt; margin: 0 0 3px; text-align: center; color: #2b3a55; }
    .meta { text-align: center; font-size: 9pt; color: #6b7c8c; }
    .rule { border-bottom: 2px solid #2b3a55; margin: 10px 0 16px; }
    table { width: 100%; border-collapse: collapse; }
    th, td { border: 1px solid #dde3e9; padding: 5px 6px; font-size: 9pt; vertical-align: top; }
    th { background-color: #f4f7f9; font-size: 8.5pt; text-align: left; color: #2b3a55; }
    td.num, th.num { width: 5%; text-align: center; }
    td.time, th.time { width: 15%; }
    td.empty { text-align: center; color: #8a9aa8; font-style: italic; }
</style></head><body>

    <h1>Rekap Jawaban Survei</h1>
    <div class="meta">Total: '.count($data['submissions']).' &nbsp;|&nbsp; Dicetak: '.now()->format('d M Y H:i').'</div>
    <div class="rule"></div>

    <table>
        <thead>
            <tr>'.$head.'</tr>
        </thead>
        <tbody>
            '.$rows.'
        </tbody>
    </table>

</body></html>';

        $dompdf = new Dompdf((new Options)->setIsRemoteEnabled(false));
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();

        $filename = 'rekap-survei-'.now()->format('Ymd_His').'.pdf';

        return response($dompdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"; filename*=UTF-8\'\''.$filename,
        ]);
    }

    /**
     * Header pertanyaan + baris jawaban yang dikelompokkan per submission_id.
     *
     * @return array{questions: Collection<int, array<string, mixed>>, submissions: Collection<int, array<string, mixed>>}
     */
    private function responsesPayload(): array
    {
        $formId = Survey::query()->orderBy('id')->value('id');

        $responses = SurveyResponse::query()
            ->with('question')
            ->when(
                $formId,
                fn ($query) => $query->where('survey_id', $formId),
            )
            ->whereNotNull('submission_id')
            ->get();

        // Group by submission_id
        $grouped = $responses->groupBy('submission_id');

        // Build questions header (only likert + text questions 1-5)
        $questions = SurveyQuestion::where('survey_id', $formId)
            ->orderBy('question_number')
            ->get()
            ->map(fn ($q) => [
                'question_number' => $q->question_number,
                'question_text' => $q->question_text,
                'type' => $q->type,
            ])
            ->values();

        $submissions = $grouped->map(function ($group) {
            $sorted = $group->sortBy('question_number');
            $first = $sorted->first();
            $answers = [];

            foreach ($sorted as $response) {
                $answers[$response->question?->question_number] = $response->answer;
            }

            return [
                'submission_id' => $group->first()->submission_id,
                'submitted_at' => $first->created_at,
                'answers' => $answers,
            ];
        })
            ->sortBy('submitted_at')
            ->values()
            ->map(fn ($submission, $index) => array_merge(['no' => $index + 1], $submission))
            ->values();

        return compact('questions', 'submissions');
    }

    /**
     * Store survey responses
     * POST /api/surveys/{survey}/responses
     */
    public function storeResponses(Request $request, Survey $survey): JsonResponse
    {
        // Check if survey is closed
        if ($survey->status === 'CLOSED') {
            return response()->json([
                'success' => false,
                'message' => 'Survey is closed and cannot accept responses',
            ], 422);
        }

        $validator = Validator::make($request->all(), [
            'responses' => 'required|array',
            'responses.*.survey_question_id' => 'required|integer|exists:survey_questions,id',
            'responses.*.answer' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $responses = DB::transaction(function () use ($request, $survey) {
                $surveyQuestions = $survey->questions()->get()->keyBy('id');
                $processedResponses = [];
                $answeredQuestions = [];

                foreach ($request->responses as $response) {
                    $questionId = $response['survey_question_id'];
                    $answer = $response['answer'];

                    // Verify question belongs to this survey
                    if (! isset($surveyQuestions[$questionId])) {
                        throw new \Exception("Question ID {$questionId} does not belong to this survey");
                    }

                    $question = $surveyQuestions[$questionId];
                    $answeredQuestions[$question->question_number] = true;

                    // Validate likert responses
                    if ($question->type === 'likert' && ! in_array($answer, $question->options ?? [])) {
                        throw new \Exception('Question '.$question->question_number.': Answer must be one of: '.implode(', ', $question->options));
                    }

                    // Use updateOrCreate
                    $surveyResponse = SurveyResponse::updateOrCreate(
                        [
                            'survey_id' => $survey->id,
                            'survey_question_id' => $questionId,
                        ],
                        [
                            'answer' => $answer,
                        ]
                    );

                    $processedResponses[] = $surveyResponse;
                }

                // Validate required questions (1-4)
                for ($i = 1; $i <= 4; $i++) {
                    if (! isset($answeredQuestions[$i])) {
                        throw new \Exception("Question {$i} is required");
                    }
                }

                return $processedResponses;
            });

            return response()->json([
                'success' => true,
                'message' => 'Responses stored successfully',
                'data' => $responses,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Close a survey
     * PATCH /api/surveys/{survey}/close
     */
    public function close(Survey $survey): JsonResponse
    {
        try {
            // Check if already closed
            if ($survey->status === 'CLOSED') {
                return response()->json([
                    'success' => false,
                    'message' => 'Survey is already closed',
                ], 422);
            }

            $survey = DB::transaction(function () use ($survey) {
                $survey->update([
                    'status' => 'CLOSED',
                    'closed_at' => now(),
                ]);

                return $survey;
            });

            return response()->json([
                'success' => true,
                'message' => 'Survey closed successfully',
                'data' => $survey,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to close survey: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update survey status and/or questions
     * PUT /api/surveys/{survey}
     */
    public function update(Request $request, Survey $survey): JsonResponse
    {
        $validated = $request->validate([
            'status' => 'sometimes|required|in:ACTIVE,CLOSED',
            'questions' => 'sometimes|array',
            'questions.*.question_number' => 'required|integer|min:1|distinct',
            'questions.*.question_text' => 'required|string',
            'questions.*.type' => 'required|in:likert,text',
            'questions.*.options' => 'nullable|array',
            'questions.*.options.*' => 'string',
        ]);

        $survey = DB::transaction(function () use ($survey, $validated) {
            if (isset($validated['status'])) {
                $survey->update([
                    'status' => $validated['status'],
                    'closed_at' => $validated['status'] === 'CLOSED' ? now() : null,
                ]);
            }

            if (isset($validated['questions'])) {
                $sentNumbers = collect($validated['questions'])->pluck('question_number');

                $survey->questions()
                    ->whereNotIn('question_number', $sentNumbers)
                    ->delete();

                foreach ($validated['questions'] as $question) {
                    $survey->questions()->updateOrCreate(
                        ['question_number' => $question['question_number']],
                        $question,
                    );
                }
            }

            return $survey;
        });

        return response()->json([
            'success' => true,
            'message' => 'Survey updated successfully',
            'data' => $survey->load('questions'),
        ], 200);
    }

    /**
     * Delete a survey along with its questions and responses
     * DELETE /api/surveys/{survey}
     */
    public function destroy(Survey $survey): JsonResponse
    {
        $survey->delete();

        return response()->json([
            'success' => true,
            'message' => 'Survey deleted successfully',
        ], 200);
    }

    /**
     * Survey publik tunggal yang dijadikan form. Dibuat otomatis bila belum ada.
     */
    private function theSurvey(): Survey
    {
        $survey = Survey::query()->orderBy('id')->first();

        if (! $survey) {
            $survey = DB::transaction(function () {
                $survey = Survey::create(['status' => 'ACTIVE']);

                foreach (Survey::DEFAULT_QUESTIONS as $question) {
                    $survey->questions()->create($question);
                }

                return $survey;
            });
        }

        return $survey;
    }
}
