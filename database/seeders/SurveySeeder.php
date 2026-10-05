<?php

namespace Database\Seeders;

use App\Models\Survey;
use Illuminate\Database\Seeder;

class SurveySeeder extends Seeder
{
    /**
     * Menanamkan form survei publik tunggal beserta 5 pertanyaan bakunya.
     */
    public function run(): void
    {
        if (Survey::query()->exists()) {
            return;
        }

        $survey = Survey::create([
            'status' => 'ACTIVE',
        ]);

        foreach (Survey::DEFAULT_QUESTIONS as $question) {
            $survey->questions()->create($question);
        }
    }
}
