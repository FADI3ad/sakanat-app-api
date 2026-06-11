<?php

namespace Database\Seeders;

use App\Models\Area;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class AreaSeeder extends Seeder
{
    public function run(): void
    {
        $areas = [
            'مدينة نصر',
            'المعادي',
            'الدقي',
            'الزمالك',
            'مصر الجديدة',
            'العباسية',
            'شبرا',
            'حلوان',
            'المنيل',
            'بولاق',
        ];

        foreach ($areas as $name) {
            Area::firstOrCreate(
                ['slug' => Str::slug($name)],
                ['name' => $name]
            );
        }
    }
}
