<?php

namespace Database\Seeders;

use App\Models\CompanySize;
use Illuminate\Database\Seeder;

class CompanySizeSeeder extends Seeder
{
    public function run(): void
    {
        $sizes = [
            ['size_code' => 'MICRO', 'size_name' => 'Mikro', 'min_employees' => 1, 'max_employees' => 4, 'range_start' => 0.00, 'range_end' => 4.00],
            ['size_code' => 'SMALL', 'size_name' => 'Kecil', 'min_employees' => 5, 'max_employees' => 19, 'range_start' => 5.00, 'range_end' => 19.00],
            ['size_code' => 'MEDIUM', 'size_name' => 'Menengah', 'min_employees' => 20, 'max_employees' => 99, 'range_start' => 20.00, 'range_end' => 99.99],
            ['size_code' => 'LARGE', 'size_name' => 'Besar', 'min_employees' => 100, 'max_employees' => null, 'range_start' => 100.00, 'range_end' => 9999999.00],
        ];

        foreach ($sizes as $size) {
            CompanySize::firstOrCreate(['size_code' => $size['size_code']], $size);
        }
    }
}
