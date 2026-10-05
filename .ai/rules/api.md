---
paths:
  - app/Http/Controllers/Api/SurveyController.php
---

# Api

## Surveys are a single public form that appends responses
Surveys were deliberately decoupled from complaints and admin_profiles (migration 2026_09_17_010809 drops both columns; removal 2026_09_17 re-removes re-added columns). The app has ONE public survey used as the form: GET /api/surveys/form returns it (created once via SurveySeeder or lazily on first access), and POST /api/surveys takes `responses[].question_number` + `answer` and APPENDS rows via create() under that survey — never updateOrCreate, or respondents overwrite each other. The 5 default questions live in Survey::DEFAULT_QUESTIONS. Q1 "Nama Petugas" and Q5 "Catatan" are free text; satisfaction only counts answer rows whose question type is likert. Legacy POST /api/surveys/{survey}/responses (keyed by survey_question_id, updateOrCreate) still exists for backward compat — keep it deprecated, do not grow it. Do not re-add complaint/admin relations or the petugas existence check.

## PUT /api/surveys/{survey} questions is replace, not patch
Editing questions via PUT /api/surveys/{survey} is replace semantics: validation requires questions[].question_number (distinct), and any question_number NOT sent is deleted along with its survey_responses (FK cascade on survey_responses.survey_question_id). Frontend must always resend the full set. A partial update needs different validation - do not add it without asking. DB CHECK on survey_questions.type allows only likert|text.
