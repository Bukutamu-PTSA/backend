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
        Schema::create('surveys', function (Blueprint $table) {
            $table->id();
            // Opsional: tautan ke tiket pengaduan bila survei berasal dari layanan aduan.
            $table->foreignId('complaint_id')->nullable()->constrained('complaints')->nullOnDelete();
            $table->foreignId('created_by_admin_id')->nullable()->constrained('admin_profiles')->nullOnDelete();
            $table->date('survey_date')->nullable();
            $table->string('nama_petugas')->nullable();
            $table->enum('komunikasi_petugas', ['baik', 'cukup', 'kurang'])->nullable();
            $table->enum('penjelasan_materi', ['baik', 'cukup', 'kurang'])->nullable();
            $table->enum('sarana_prasarana', ['baik', 'cukup', 'kurang'])->nullable();
            $table->text('catatan')->nullable();
            $table->unsignedTinyInteger('rating_overall')->nullable();
            $table->boolean('is_anonymous')->default(false);
            $table->string('respondent_email')->nullable();
            $table->string('respondent_phone', 20)->nullable();
            $table->enum('status', ['draft', 'submitted'])->default('draft');
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();

            $table->index('complaint_id');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('surveys');
    }
};
