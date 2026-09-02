<?php

namespace App\Http\Controllers\Api;

use App\Models\AdminProfile;
use App\Models\Survey;
use App\Models\SurveyQuestion;
use App\Models\SurveyResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class SurveyController
{
    /**
     * Create a new survey with predefined questions
     * POST /api/surveys
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'complaint_id' => 'required|exists:complaints,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $survey = DB::transaction(function () use ($request) {
                // Get admin profile dari user yang login
                $adminProfile = AdminProfile::where('user_id', auth()->id())->firstOrFail();

                // Create survey
                $survey = Survey::create([
                    'complaint_id' => $request->complaint_id,
                    'created_by_admin_id' => $adminProfile->id,
                    'status' => 'ACTIVE',
                ]);

                // Define survey questions
                $questions = [
                    [
                        'question_number' => 1,
                        'question_text' => 'Nama Petugas',
                        'type' => 'text',
                        'options' => null,
                    ],
                    [
                        'question_number' => 2,
                        'question_text' => 'Bagaimana Komunikasi Petugas Dalam Memberikan Layanan',
                        'type' => 'likert',
                        'options' => ['Baik', 'Cukup', 'Kurang'],
                    ],
                    [
                        'question_number' => 3,
                        'question_text' => 'Bagaimana Penjelasan Materi yang Diberikan oleh Petugas',
                        'type' => 'likert',
                        'options' => ['Baik', 'Cukup', 'Kurang'],
                    ],
                    [
                        'question_number' => 4,
                        'question_text' => 'Bagaimana Sarana dan Prasarana Layanan PTSA',
                        'type' => 'likert',
                        'options' => ['Baik', 'Cukup', 'Kurang'],
                    ],
                    [
                        'question_number' => 5,
                        'question_text' => 'Catatan',
                        'type' => 'text',
                        'options' => null,
                    ],
                ];

                // Create survey questions
                foreach ($questions as $question) {
                    SurveyQuestion::create([
                        'survey_id' => $survey->id,
                        'question_number' => $question['question_number'],
                        'question_text' => $question['question_text'],
                        'type' => $question['type'],
                        'options' => $question['options'],
                    ]);
                }

                return $survey->load('questions');
            });

            return response()->json([
                'success' => true,
                'data' => $survey,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create survey: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get survey details
     * GET /api/surveys/{survey}
     */
    public function show(Survey $survey): JsonResponse
    {
        try {
            $survey->load([
                'complaint',
                'admin',
                'questions',
            ]);

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
                    if (!isset($surveyQuestions[$questionId])) {
                        throw new \Exception("Question ID {$questionId} does not belong to this survey");
                    }

                    $question = $surveyQuestions[$questionId];
                    $answeredQuestions[$question->question_number] = true;

                    // Validate Nama Petugas
                    if ($question->question_number === 1) {
                        $isValidPetugas = AdminProfile::where('nama_petugas', $answer)->exists();

                        if (!$isValidPetugas) {
                            throw new \Exception("Nama Petugas tidak terdaftar");
                        }
                    }

                    // Validate likert responses
                    if ($question->type === 'likert') {
                        if (!in_array($answer, ['Baik', 'Cukup', 'Kurang'])) {
                            throw new \Exception("Question {$question->question_number}: Answer must be 'Baik', 'Cukup', or 'Kurang'");
                        }
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
                    if (!isset($answeredQuestions[$i])) {
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
                'message' => 'Failed to close survey: ' . $e->getMessage(),
            ], 500);
        }
    }
}
