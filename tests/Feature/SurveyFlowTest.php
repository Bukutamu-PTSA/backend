<?php

namespace Tests\Feature;

use App\Models\Survey;
use App\Models\SurveyQuestion;
use App\Models\SurveyResponse;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SurveyFlowTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @param  array<int, string>  $answersByNumber
     * @return array<string, mixed>
     */
    private function answersPayload(array $answersByNumber): array
    {
        return [
            'responses' => collect($answersByNumber)
                ->map(fn (string $answer, int $number) => [
                    'question_number' => $number,
                    'answer' => $answer,
                ])
                ->values()
                ->all(),
        ];
    }

    public function test_public_form_returns_the_single_survey_with_five_questions(): void
    {
        $response = $this->getJson('/api/surveys/form');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', 'ACTIVE')
            ->assertJsonCount(5, 'data.questions')
            ->assertJsonMissingPath('data.complaint')
            ->assertJsonMissingPath('data.created_by_admin');

        $this->assertSame(1, Survey::count());
        $this->assertSame(5, SurveyQuestion::count());
    }

    public function test_form_does_not_create_a_new_survey_on_every_call(): void
    {
        $this->getJson('/api/surveys/form')->assertOk();
        $this->getJson('/api/surveys/form')->assertOk();

        $this->assertSame(1, Survey::count());
    }

    public function test_anyone_can_submit_answers_anonymously_to_the_public_form(): void
    {
        $formId = $this->getJson('/api/surveys/form')->json('data.id');

        $this->postJson('/api/surveys', $this->answersPayload([
            1 => 'Petugas Tidak Terdaftar',
            2 => 'Baik',
            3 => 'Cukup',
            4 => 'Kurang',
        ]))->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.0.survey_id', $formId);

        $this->assertSame(1, Survey::count());
        $this->assertSame(4, SurveyResponse::count());
        $this->assertDatabaseHas('survey_responses', ['survey_id' => $formId, 'answer' => 'Baik']);
    }

    public function test_each_submission_appends_new_responses_to_the_same_survey(): void
    {
        $formId = $this->getJson('/api/surveys/form')->json('data.id');

        $this->postJson('/api/surveys', $this->answersPayload([
            1 => 'Petugas A', 2 => 'Baik', 3 => 'Baik', 4 => 'Baik',
        ]))->assertCreated();

        $this->postJson('/api/surveys', $this->answersPayload([
            1 => 'Petugas B', 2 => 'Kurang', 3 => 'Kurang', 4 => 'Kurang',
        ]))->assertCreated();

        $this->assertSame(1, Survey::count());
        $this->assertSame(8, SurveyResponse::count());
        $this->assertSame(3, SurveyResponse::where('answer', 'Baik')->count());
        $this->assertSame(3, SurveyResponse::where('answer', 'Kurang')->count());
    }

    public function test_submit_requires_questions_1_to_4(): void
    {
        $this->postJson('/api/surveys', $this->answersPayload([
            1 => 'Petugas', 2 => 'Baik', 3 => 'Baik',
        ]))->assertUnprocessable()
            ->assertJsonPath('message', 'Question 4 is required');

        $this->assertSame(0, SurveyResponse::count());
    }

    public function test_likert_answers_must_be_a_valid_option(): void
    {
        $this->postJson('/api/surveys', $this->answersPayload([
            1 => 'Petugas', 2 => 'Luar Biasa', 3 => 'Baik', 4 => 'Baik',
        ]))->assertUnprocessable();

        $this->assertSame(0, SurveyResponse::count());
    }

    public function test_question_number_must_be_part_of_the_survey(): void
    {
        $this->postJson('/api/surveys', [
            'responses' => [
                ['question_number' => 99, 'answer' => 'Baik'],
            ],
        ])->assertUnprocessable()
            ->assertJsonPath('message', 'Question number 99 is not part of the survey');

        $this->assertSame(0, SurveyResponse::count());
    }

    public function test_closed_form_rejects_submissions(): void
    {
        $formId = $this->getJson('/api/surveys/form')->json('data.id');
        Survey::findOrFail($formId)->update(['status' => 'CLOSED']);

        $this->postJson('/api/surveys', $this->answersPayload([
            1 => 'Petugas', 2 => 'Baik', 3 => 'Baik', 4 => 'Baik',
        ]))->assertUnprocessable()
            ->assertJsonPath('message', 'Survey is closed and cannot accept responses');

        $this->assertSame(0, SurveyResponse::count());
    }

    public function test_satisfaction_index_only_counts_likert_question_types(): void
    {
        $this->postJson('/api/surveys', $this->answersPayload([
            1 => 'Baik',
            2 => 'Baik',
            3 => 'Cukup',
            4 => 'Kurang',
        ]))->assertCreated();

        $this->getJson('/api/surveys/satisfaction')
            ->assertOk()
            ->assertJsonPath('data.satisfaction_index', 3)
            ->assertJsonPath('data.satisfaction_scale', 5)
            ->assertJsonPath('data.satisfaction_responses', 3)
            ->assertJsonPath('meta.period', 'all');
    }

    public function test_satisfaction_index_is_null_when_no_likert_answers_exist(): void
    {
        $this->getJson('/api/surveys/satisfaction')
            ->assertOk()
            ->assertJsonPath('data.satisfaction_index', null)
            ->assertJsonPath('data.satisfaction_responses', 0);
    }

    public function test_survey_responses_requires_admin_auth(): void
    {
        $this->getJson('/api/surveys/responses')->assertUnauthorized();

        $user = User::create([
            'username' => 'survey_user',
            'email' => 'survey_user@mail.com',
            'password' => bcrypt('password'),
            'role' => 'pelapor',
        ]);

        $this->actingAs($user)->getJson('/api/surveys/responses')->assertForbidden();
    }

    public function test_admin_sees_one_row_per_submission_with_answers_by_question(): void
    {
        $this->postJson('/api/surveys', $this->answersPayload([
            1 => 'Petugas Andi', 2 => 'Baik', 3 => 'Cukup', 4 => 'Kurang', 5 => 'Bagus',
        ]))->assertCreated();

        $this->postJson('/api/surveys', $this->answersPayload([
            1 => 'Petugas Budi', 2 => 'Kurang', 3 => 'Kurang', 4 => 'Baik',
        ]))->assertCreated();

        $admin = User::create([
            'username' => 'survey_admin',
            'email' => 'survey_admin@mail.com',
            'password' => bcrypt('password'),
            'role' => 'admin',
        ]);

        $response = $this->actingAs($admin)->getJson('/api/surveys/responses');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(5, 'data.questions')
            ->assertJsonCount(2, 'data.submissions');

        $submissions = $response->json('data.submissions');

        $this->assertSame(1, $submissions[0]['no']);
        $this->assertSame('Petugas Andi', $submissions[0]['answers']['1']);
        $this->assertSame('Baik', $submissions[0]['answers']['2']);
        $this->assertSame('Cukup', $submissions[0]['answers']['3']);
        $this->assertSame('Kurang', $submissions[0]['answers']['4']);
        $this->assertSame('Bagus', $submissions[0]['answers']['5']);
        $this->assertArrayHasKey('submitted_at', $submissions[0]);

        $this->assertSame(2, $submissions[1]['no']);
        $this->assertSame('Petugas Budi', $submissions[1]['answers']['1']);
        $this->assertArrayNotHasKey('5', $submissions[1]['answers']);
    }

    public function test_survey_responses_pdf_requires_admin_auth(): void
    {
        $this->get('/api/surveys/responses/pdf')->assertUnauthorized();

        $user = User::create([
            'username' => 'survey_pdf_user',
            'email' => 'survey_pdf_user@mail.com',
            'password' => bcrypt('password'),
            'role' => 'pelapor',
        ]);

        $this->actingAs($user)->get('/api/surveys/responses/pdf')->assertForbidden();
    }

    public function test_admin_can_download_survey_responses_pdf(): void
    {
        $this->postJson('/api/surveys', $this->answersPayload([
            1 => 'Petugas Andi', 2 => 'Baik', 3 => 'Cukup', 4 => 'Kurang', 5 => 'Bagus',
        ]))->assertCreated();

        $admin = User::create([
            'username' => 'survey_pdf_admin',
            'email' => 'survey_pdf_admin@mail.com',
            'password' => bcrypt('password'),
            'role' => 'admin',
        ]);

        $response = $this->actingAs($admin)->get('/api/surveys/responses/pdf');

        $response->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');

        $this->assertStringContainsString(
            'filename="rekap-survei-',
            $response->headers->get('Content-Disposition')
        );

        $this->assertStringStartsWith('%PDF-', $response->getContent());
    }

    public function test_survey_update_requires_admin_auth(): void
    {
        $formId = $this->getJson('/api/surveys/form')->json('data.id');

        $this->putJson("/api/surveys/{$formId}", ['status' => 'CLOSED'])->assertUnauthorized();

        $user = User::create([
            'username' => 'survey_pelapor',
            'email' => 'survey_pelapor@mail.com',
            'password' => bcrypt('password'),
            'role' => 'pelapor',
        ]);

        $this->actingAs($user)->putJson("/api/surveys/{$formId}", ['status' => 'CLOSED'])->assertForbidden();
    }

    public function test_admin_can_update_survey_status_and_questions(): void
    {
        $formId = $this->getJson('/api/surveys/form')->json('data.id');

        $admin = User::create([
            'username' => 'survey_admin',
            'email' => 'survey_admin2@mail.com',
            'password' => bcrypt('password'),
            'role' => 'admin',
        ]);

        $this->actingAs($admin)->putJson("/api/surveys/{$formId}", [
            'questions' => [
                ['question_number' => 1, 'question_text' => 'Petugas', 'type' => 'text', 'options' => null],
                ['question_number' => 2, 'question_text' => 'Komunikasi', 'type' => 'likert', 'options' => ['Baik', 'Cukup', 'Kurang']],
                ['question_number' => 3, 'question_text' => 'Saran', 'type' => 'text', 'options' => null],
            ],
        ])->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(3, 'data.questions');

        $this->assertSame(1, SurveyQuestion::where('survey_id', $formId)->where('question_text', 'Komunikasi')->count());
        $this->assertDatabaseMissing('survey_questions', ['survey_id' => $formId, 'question_number' => 4]);
    }

    public function test_admin_can_reopen_a_closed_survey(): void
    {
        $formId = $this->getJson('/api/surveys/form')->json('data.id');
        Survey::findOrFail($formId)->update(['status' => 'CLOSED', 'closed_at' => now()]);

        $admin = User::create([
            'username' => 'survey_admin',
            'email' => 'survey_admin3@mail.com',
            'password' => bcrypt('password'),
            'role' => 'admin',
        ]);

        $this->actingAs($admin)->putJson("/api/surveys/{$formId}", ['status' => 'ACTIVE'])
            ->assertOk()
            ->assertJsonPath('data.status', 'ACTIVE')
            ->assertJsonPath('data.closed_at', null);
    }

    public function test_survey_delete_requires_admin_auth_and_cascades(): void
    {
        $formId = $this->getJson('/api/surveys/form')->json('data.id');
        $this->postJson('/api/surveys', $this->answersPayload([
            1 => 'Petugas', 2 => 'Baik', 3 => 'Baik', 4 => 'Baik',
        ]))->assertCreated();

        $this->deleteJson("/api/surveys/{$formId}")->assertUnauthorized();

        $admin = User::create([
            'username' => 'survey_admin',
            'email' => 'survey_admin4@mail.com',
            'password' => bcrypt('password'),
            'role' => 'admin',
        ]);

        $this->actingAs($admin)->deleteJson("/api/surveys/{$formId}")
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertSame(0, Survey::count());
        $this->assertSame(0, SurveyQuestion::count());
        $this->assertSame(0, SurveyResponse::count());
    }

    public function test_form_recreates_after_survey_is_deleted(): void
    {
        $formId = $this->getJson('/api/surveys/form')->json('data.id');

        $admin = User::create([
            'username' => 'survey_admin',
            'email' => 'survey_admin5@mail.com',
            'password' => bcrypt('password'),
            'role' => 'admin',
        ]);

        $this->actingAs($admin)->deleteJson("/api/surveys/{$formId}")->assertOk();

        $this->getJson('/api/surveys/form')->assertOk()
            ->assertJsonCount(5, 'data.questions');
        $this->assertSame(1, Survey::count());
    }
}
