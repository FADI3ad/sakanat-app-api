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
                'slug' => \Illuminate\Support\Str::slug('خدمات الطباعة والتصوير'),
                'title' => 'خدمات الطباعة والتصوير',
                'description' => 'نقدم خدمات الطباعة والتصوير للوثائق والمواد المختلفة.',
                'icon' => null,
                'status' => true,
                'sort_order' => 1,
            ],
            [
                'slug' => \Illuminate\Support\Str::slug('خدمات الغسيل والمكواة'),
                'title' => 'خدمات الغسيل والمكواة',
                'description' => 'نقدم خدمات الغسيل والمكواة للملابس والمواد المختلفة.',
                'icon' => null,
                'status' => true,
                'sort_order' => 2,
            ],
            [
                'slug' => \Illuminate\Support\Str::slug('خدمات المواصلات'),
                'title' => 'خدمات المواصلات',
                'description' => 'نقدم خدمات المواصلات لنقل الأشخاص والبضائع.',
                'icon' => null,
                'status' => true,
                'sort_order' => 3,
            ],
            [
                'slug' => \Illuminate\Support\Str::slug('خدمات الطعام والتوصيل'),
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
