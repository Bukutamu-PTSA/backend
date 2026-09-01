<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('complaints')->where('status', 'BARU')->update(['status' => 'DIPROSES']);

        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'pgsql') {
            DB::statement('ALTER TABLE complaints DROP CONSTRAINT IF EXISTS complaints_status_check');
            DB::statement('ALTER TABLE complaints ALTER COLUMN status DROP DEFAULT');
            DB::statement('ALTER TABLE complaints ALTER COLUMN status TYPE varchar(255) USING status::text');
            DB::statement("ALTER TABLE complaints ADD CONSTRAINT complaints_status_check CHECK (status IN ('DIPROSES','SELESAI'))");
            DB::statement("ALTER TABLE complaints ALTER COLUMN status SET DEFAULT 'DIPROSES'");
        } elseif ($driver === 'mysql') {
            Schema::table('complaints', function (Blueprint $table) {
                $table->enum('status', ['DIPROSES', 'SELESAI'])->default('DIPROSES')->change();
            });
        }
    }

    public function down(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'pgsql') {
            DB::statement('ALTER TABLE complaints DROP CONSTRAINT IF EXISTS complaints_status_check');
            DB::statement('ALTER TABLE complaints ALTER COLUMN status DROP DEFAULT');
            DB::statement('DROP TYPE IF EXISTS complaints_status');
            DB::statement("CREATE TYPE complaints_status AS ENUM ('BARU','DIPROSES','SELESAI')");
            DB::statement('ALTER TABLE complaints ALTER COLUMN status TYPE complaints_status USING status::complaints_status');
            DB::statement("ALTER TABLE complaints ALTER COLUMN status SET DEFAULT 'BARU'");
        } elseif ($driver === 'mysql') {
            Schema::table('complaints', function (Blueprint $table) {
                $table->enum('status', ['BARU', 'DIPROSES', 'SELESAI'])->default('BARU')->change();
            });
        }
    }
};