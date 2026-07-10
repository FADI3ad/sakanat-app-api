<?php

namespace Database\Seeders;

use App\Models\Type;
use Illuminate\Database\Seeder;

class TypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $types = [
            [
                'name'        => 'خدمات الطباعة والتصوير',
                'description' => 'نقدم خدمات الطباعة والتصوير للوثائق والمواد المختلفة.',
                'icon'        => null,
                'status'      => true,
                'sort_order'  => 1,
            ],
            [
                'name'        => 'خدمات الغسيل والمكواة',
                'description' => 'نقدم خدمات الغسيل والمكواة للملابس والمواد المختلفة.',
                'icon'        => null,
                'status'      => true,
                'sort_order'  => 2,
            ],
            [
                'name'        => 'خدمات المواصلات',
                'description' => 'نقدم خدمات المواصلات لنقل الأشخاص والبضائع.',
                'icon'        => null,
                'status'      => true,
                'sort_order'  => 3,
            ],
            [
                'name'        => 'خدمات الطعام والتوصيل',
                'description' => 'نقدم خدمات الطعام والتوصيل للطلبات المختلفة.',
                'icon'        => null,
                'status'      => true,
                'sort_order'  => 4,
            ],
        ];

        foreach ($types as $type) {
            Type::create($type);
        }
    }
}
