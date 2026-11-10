<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const COLUMNS = [
        'admin_profiles' => ['created_at', 'updated_at'],
        'app_settings' => ['created_at', 'updated_at'],
        'attachment_categories' => ['created_at', 'updated_at'],
        'attachments' => ['created_at', 'updated_at'],
        'companies' => ['created_at', 'updated_at'],
        'company_sizes' => ['created_at', 'updated_at'],
        'complainants' => ['created_at', 'updated_at'],
        'complaint_categories' => ['created_at', 'updated_at'],
        'complaints' => ['complaint_date', 'created_at', 'updated_at'],
        'failed_jobs' => ['failed_at'],
        'faqs' => ['created_at', 'updated_at'],
        'indonesia_cities' => ['created_at', 'updated_at'],
        'indonesia_districts' => ['created_at', 'updated_at'],
        'indonesia_provinces' => ['created_at', 'updated_at'],
        'indonesia_villages' => ['created_at', 'updated_at'],
        'industrial_sectors' => ['created_at', 'updated_at'],
        'notifications' => ['created_at', 'updated_at'],
        'password_reset_tokens' => ['created_at'],
        'personal_access_tokens' => ['created_at', 'expires_at', 'last_used_at', 'updated_at'],
        'survey_question_templates' => ['created_at', 'updated_at'],
        'survey_questions' => ['created_at', 'updated_at'],
        'survey_responses' => ['created_at', 'updated_at'],
        'surveys' => ['closed_at', 'created_at', 'updated_at'],
        'updates' => ['created_at', 'updated_at'],
        'users' => ['created_at', 'email_verified_at', 'updated_at'],
    ];

    public function up(): void
    {
        $this->shift('+');
    }

    public function down(): void
    {
        $this->shift('-');
    }

    private function shift(string $operator): void
    {
        foreach (self::COLUMNS as $table => $columns) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            foreach ($columns as $column) {
                if (! Schema::hasColumn($table, $column)) {
                    continue;
                }

                if (DB::connection()->getDriverName() === 'sqlite') {
                    DB::statement(
                        "UPDATE {$table} SET {$column} = datetime({$column}, '{$operator}7 hours') WHERE {$column} IS NOT NULL"
                    );

                    continue;
                }

                DB::statement(
                    "UPDATE {$table} SET {$column} = {$column} {$operator} interval '7 hours' WHERE {$column} IS NOT NULL"
                );
            }
        }
    }
};
