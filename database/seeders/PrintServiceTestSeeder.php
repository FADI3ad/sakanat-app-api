<?php

namespace Database\Seeders;

use App\Enums\UserTypeEnum;
use App\Models\Area;
use App\Models\PrintService;
use App\Models\Provider;
use App\Models\Service;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class PrintServiceTestSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('🌱 بدء الـ seeder ...');

        // ─────────────────────────────────────────
        // 1. Areas
        // ─────────────────────────────────────────
        $this->command->info('📍 إنشاء المناطق ...');

        $areas = collect([
            'مدينة نصر',
            'المعادي',
            'الدقي',
            'الزمالك',
            'مصر الجديدة',
        ])->map(fn($name) => Area::firstOrCreate(
            ['slug' => Str::slug($name)],
            ['name' => $name]
        ));

        // ─────────────────────────────────────────
        // 2. Service (خدمات الطباعة والتصوير)
        // ─────────────────────────────────────────
        $this->command->info('🗂️ إنشاء خدمة الطباعة والتصوير ...');

        $printServiceType = Service::firstOrCreate(
            ['slug' => Str::slug('خدمات الطباعة والتصوير')],
            [
                'title'       => 'خدمات الطباعة والتصوير',
                'description' => 'نقدم خدمات الطباعة والتصوير للوثائق والمواد المختلفة.',
                'icon'        => null,
                'status'      => true,
                'sort_order'  => 1,
            ]
        );

        // ─────────────────────────────────────────
        // 3. Provider users + providers + listings
        // ─────────────────────────────────────────
        $this->command->info('👷 إنشاء مزودي خدمة الطباعة ...');

        $providersData = [
            [
                'user' => [
                    'name'     => 'أحمد محمود - طباعة',
                    'email'    => 'ahmed.print@test.com',
                    'phone'    => '01000000001',
                    'password' => Hash::make('password'),
                    'type'     => UserTypeEnum::PROVIDER->value,
                ],
                'listing' => [
                    'slug'                          => 'print-ahmed-nasr',
                    'title'                         => 'مركز أحمد للطباعة - مدينة نصر',
                    'description'                   => 'خدمات طباعة سريعة وعالية الجودة، متاح 24 ساعة.',
                    'is_available'                  => true,
                    'delevery_available'            => true,
                    'has_color_option'              => true,
                    'black_and_white_price_per_page' => 0.50,
                    'color_price_per_page'          => 2.00,
                    'area'                          => 'مدينة نصر',
                ],
            ],
            [
                'user' => [
                    'name'     => 'سارة علي - طباعة',
                    'email'    => 'sara.print@test.com',
                    'phone'    => '01000000002',
                    'password' => Hash::make('password'),
                    'type'     => UserTypeEnum::PROVIDER->value,
                ],
                'listing' => [
                    'slug'                          => 'print-sara-maadi',
                    'title'                         => 'مركز سارة للطباعة - المعادي',
                    'description'                   => 'طباعة ليزر وإنكجت، تجليد، لاميناشن.',
                    'is_available'                  => true,
                    'delevery_available'            => false,
                    'has_color_option'              => true,
                    'black_and_white_price_per_page' => 0.40,
                    'color_price_per_page'          => 1.75,
                    'area'                          => 'المعادي',
                ],
            ],
            [
                'user' => [
                    'name'     => 'محمد خالد - طباعة',
                    'email'    => 'mohamed.print@test.com',
                    'phone'    => '01000000003',
                    'password' => Hash::make('password'),
                    'type'     => UserTypeEnum::PROVIDER->value,
                ],
                'listing' => [
                    'slug'                          => 'print-mohamed-dokki',
                    'title'                         => 'مركز محمد للطباعة - الدقي',
                    'description'                   => 'طباعة وتصوير، خدمة التوصيل للجامعات.',
                    'is_available'                  => true,
                    'delevery_available'            => true,
                    'has_color_option'              => false,
                    'black_and_white_price_per_page' => 0.35,
                    'color_price_per_page'          => 0.00,
                    'area'                          => 'الدقي',
                ],
            ],
        ];

        foreach ($providersData as $data) {
            // Create user
            $user = User::firstOrCreate(
                ['email' => $data['user']['email']],
                $data['user']
            );

            // Create provider
            $provider = Provider::firstOrCreate(
                ['user_id' => $user->id]
            );

            // Resolve area
            $area = $areas->firstWhere('name', $data['listing']['area']);

            // Create print listing
            PrintService::firstOrCreate(
                ['slug' => $data['listing']['slug']],
                [
                    'title'                          => $data['listing']['title'],
                    'description'                    => $data['listing']['description'],
                    'is_available'                   => $data['listing']['is_available'],
                    'delevery_available'             => $data['listing']['delevery_available'],
                    'has_color_option'               => $data['listing']['has_color_option'],
                    'black_and_white_price_per_page' => $data['listing']['black_and_white_price_per_page'],
                    'color_price_per_page'           => $data['listing']['color_price_per_page'],
                    'provider_id'                    => $provider->id,
                    'service_id'                     => $printServiceType->id,
                    'area_id'                        => $area->id,
                ]
            );

            $this->command->info("  ✅ {$data['listing']['title']}");
        }

        $this->command->newLine();
        $this->command->info('🎉 تم الانتهاء! جرب الـ endpoint:');
        $this->command->info('   GET /api/v1/services/' . $printServiceType->slug . '/listings');
        $this->command->newLine();
    }
}
