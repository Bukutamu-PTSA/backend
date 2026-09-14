<?php

namespace Database\Seeders;

use App\Models\ComplaintType;
use Illuminate\Database\Seeder;

class ComplaintTypeSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            ['type_code' => 'LISENSI', 'type_name' => 'Lisensi'],
            ['type_code' => 'SKP', 'type_name' => 'SKP'],
            ['type_code' => 'UPAH_KERJA', 'type_name' => 'Upah Kerja'],
            ['type_code' => 'K3', 'type_name' => 'Keselamatan & Kesehatan Kerja'],
            ['type_code' => 'PHK', 'type_name' => 'Pemutusan Hubungan Kerja'],
        ];

        foreach ($types as $type) {
            ComplaintType::firstOrCreate(['type_code' => $type['type_code']], $type);
        }
    }
}
