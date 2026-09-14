<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Bank pertanyaan survei (DATA_SURVEI_QUESTION) yang berdiri sendiri.
        Schema::create('survey_questions', function (Blueprint $table) {
            $table->id();
            $table->text('question_text');
            $table->enum('answer_type', ['radio', 'multiple_choice', 'text_area'])->default('radio');
            $table->string('option_a')->nullable();
            $table->string('option_b')->nullable();
            $table->string('option_c')->nullable();
            $table->boolean('is_required')->default(true);
            $table->unsignedSmallInteger('order_number')->default(0);
            $table->timestamps();

            $table->index('order_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('survey_questions');
    }
};
