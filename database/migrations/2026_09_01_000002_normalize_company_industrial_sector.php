<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('companies') || ! Schema::hasColumn('companies', 'sektor_industri')) {
            return;
        }

        if (! Schema::hasColumn('companies', 'sector_id')) {
            Schema::table('companies', function (Blueprint $table) {
                $table->foreignId('sector_id')->nullable()->constrained('industrial_sectors')->restrictOnDelete();
            });
        }

        $sectors = [
            ['sector_code' => 'AGRICULTURE', 'sector_name' => 'Pertanian, Kehutanan, dan Perikanan'],
            ['sector_code' => 'MINING', 'sector_name' => 'Pertambangan dan Penggalian'],
            ['sector_code' => 'MANUFACTURING', 'sector_name' => 'Industri Pengolahan'],
            ['sector_code' => 'CONSTRUCTION', 'sector_name' => 'Konstruksi'],
            ['sector_code' => 'TRADE', 'sector_name' => 'Perdagangan'],
            ['sector_code' => 'TRANSPORTATION', 'sector_name' => 'Transportasi dan Pergudangan'],
            ['sector_code' => 'ACCOMMODATION', 'sector_name' => 'Penyediaan Akomodasi dan Makan Minum'],
            ['sector_code' => 'INFORMATION', 'sector_name' => 'Informasi dan Komunikasi'],
            ['sector_code' => 'FINANCE', 'sector_name' => 'Jasa Keuangan dan Asuransi'],
            ['sector_code' => 'SERVICES', 'sector_name' => 'Jasa'],
        ];

        $now = now();
        DB::table('industrial_sectors')->upsert(
            array_map(fn (array $sector) => [...$sector, 'created_at' => $now, 'updated_at' => $now], $sectors),
            ['sector_code'],
            ['sector_name', 'updated_at']
        );

        $aliases = [
            'AGRICULTURE' => ['AGRICULTURE', 'Pertanian, Kehutanan, dan Perikanan'],
            'MINING' => ['MINING', 'Pertambangan dan Penggalian'],
            'MANUFACTURING' => ['MANUFACTURING', 'Industri Pengolahan', 'Manufaktur'],
            'CONSTRUCTION' => ['CONSTRUCTION', 'Konstruksi'],
            'TRADE' => ['TRADE', 'Perdagangan'],
            'TRANSPORTATION' => ['TRANSPORTATION', 'Transportasi dan Pergudangan'],
            'ACCOMMODATION' => ['ACCOMMODATION', 'Penyediaan Akomodasi dan Makan Minum'],
            'INFORMATION' => ['INFORMATION', 'Informasi dan Komunikasi'],
            'FINANCE' => ['FINANCE', 'Jasa Keuangan dan Asuransi'],
            'SERVICES' => ['SERVICES', 'Jasa'],
        ];

        foreach (DB::table('companies')->select('sektor_industri')->distinct()->get() as $company) {
            $legacyName = trim((string) $company->sektor_industri);
            $code = collect($aliases)->search(fn (array $values) => in_array($legacyName, $values, true));

            if ($code === false) {
                $code = $legacyName === ''
                    ? 'LEGACY_UNSPECIFIED'
                    : 'LEGACY_'.strtoupper(substr(sha1($legacyName), 0, 12));
                DB::table('industrial_sectors')->updateOrInsert(
                    ['sector_code' => $code],
                    ['sector_name' => $legacyName === '' ? 'Tidak ditentukan' : $legacyName, 'created_at' => $now, 'updated_at' => $now]
                );
            }

            $sectorId = DB::table('industrial_sectors')->where('sector_code', $code)->value('id');
            DB::table('companies')->where('sektor_industri', $company->sektor_industri)->update(['sector_id' => $sectorId]);
        }

        Schema::table('companies', function (Blueprint $table) {
            $table->foreignId('sector_id')->nullable(false)->change();
            $table->dropColumn('sektor_industri');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('companies') || ! Schema::hasColumn('companies', 'sector_id')) {
            return;
        }

        Schema::table('companies', function (Blueprint $table) {
            $table->string('sektor_industri')->nullable();
        });

        foreach (DB::table('companies')->select('id', 'sector_id')->get() as $company) {
            DB::table('companies')->where('id', $company->id)->update([
                'sektor_industri' => DB::table('industrial_sectors')->where('id', $company->sector_id)->value('sector_name'),
            ]);
        }

        Schema::table('companies', function (Blueprint $table) {
            $table->dropForeign(['sector_id']);
            $table->dropColumn('sector_id');
        });
    }
};
