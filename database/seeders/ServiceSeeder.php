<?php

namespace Database\Seeders;

use App\Models\Service;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ServiceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $services = [
            [
                'title' => 'خدمات الطباعة والتصوير',
                'description' => 'نقدم خدمات الطباعة والتصوير للوثائق والمواد المختلفة.',
                'icon' => null,
                'status' => true,
                'sort_order' => 1,
            ],
            [
                'title' => 'خدمات الغسيل والمكواة',
                'description' => 'نقدم خدمات الغسيل والمكواة للملابس والمواد المختلفة.',
                'icon' => null,
                'status' => true,
                'sort_order' => 2,
            ],
            [
                'title' => 'خدمات المواصلات',
                'description' => 'نقدم خدمات المواصلات لنقل الأشخاص والبضائع.',
                'icon' => null,
                'status' => true,
                'sort_order' => 3,
            ],
            [
                'title' => 'خدمات الطعام والتوصيل',
                'description' => 'نقدم خدمات الطعام والتوصيل للطلبات المختلفة.',
                'icon' => null,
                'status' => true,
                'sort_order' => 4,
            ],
        ];

        foreach ($services as $service) {
            Service::create($service);
        }
    }
}
