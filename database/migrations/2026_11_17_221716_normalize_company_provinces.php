<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const JAKARTA = 'DAERAH KHUSUS IBUKOTA JAKARTA';

    /**
     * Singkatan dan nama kota/kecamatan yang terisi di kolom provinsi, dipetakan ke nama
     * provinsi sesuai tabel indonesia_provinces (laravolt).
     *
     * @var array<string, string>
     */
    private const ALIASES = [
        'JABAR' => 'JAWA BARAT',
        'JATENG' => 'JAWA TENGAH',
        'JATIM' => 'JAWA TIMUR',
        'JAWA TIRMUR' => 'JAWA TIMUR',
        'NTB' => 'NUSA TENGGARA BARAT',
        'NTT' => 'NUSA TENGGARA TIMUR',
        'DENPASAR' => 'BALI',
        'MARGOREJO' => 'JAWA TENGAH',
        'TANGERANG SELATAN' => 'BANTEN',
    ];

    public function up(): void
    {
        $values = DB::table('companies')->whereNotNull('provinsi')->distinct()->pluck('provinsi');

        foreach ($values as $original) {
            $normalized = $this->normalize((string) $original);

            if ($normalized !== $original) {
                DB::table('companies')->where('provinsi', $original)->update(['provinsi' => $normalized]);
            }
        }
    }

    public function down(): void
    {
        // Data lama tidak dapat dikembalikan.
    }

    private function normalize(string $value): string
    {
        $clean = mb_strtoupper(trim(preg_replace('/\s+/', ' ', $value)));

        if (isset(self::ALIASES[$clean])) {
            return self::ALIASES[$clean];
        }

        if (preg_match('/^DKI\b|JAK|JKT|^DAERAH KHUSUS/', $clean) === 1) {
            return self::JAKARTA;
        }

        return $clean;
    }
};
