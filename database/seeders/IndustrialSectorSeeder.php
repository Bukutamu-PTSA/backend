<?php

namespace Database\Seeders;

use App\Models\IndustrialSector;
use Illuminate\Database\Seeder;

class IndustrialSectorSeeder extends Seeder
{
    public function run(): void
    {
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

        foreach ($sectors as $sector) {
            IndustrialSector::firstOrCreate(['sector_code' => $sector['sector_code']], $sector);
        }
    }
}
