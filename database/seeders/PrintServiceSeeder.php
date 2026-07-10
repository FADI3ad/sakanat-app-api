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

class PrintServiceSeeder extends Seeder
{
    public function run(): void
    {
        $printServiceType = Service::where('title', 'خدمات الطباعة والتصوير')->first();

        if (!$printServiceType) {
            $this->command->warn('⚠️  لم يتم العثور على خدمة الطباعة، تأكد من تشغيل ServiceSeeder أولاً.');
            return;
        }

        $providersData = [
            [
                'user' => [
                    'name'     => 'ابانوب',
                    'email'    => 'ahmed.print@test.com',
                    'phone'    => '01000000001',
                    'password' => Hash::make('password'),
                    'type'     => UserTypeEnum::PROVIDER->value,
                ],
                'listing' => [
                    'slug'                           => 'print-ahmed-nasr',
                    'title'                          => 'مركز ابانوب للطباعة',
                    'description'                    => 'خدمات طباعة سريعة وعالية الجودة، متاح 24 ساعة.',
                    'is_available'                   => true,
                    'delevery_available'             => true,
                    'has_color_option'               => true,
                    'black_and_white_price_per_page' => 0.50,
                    'color_price_per_page'           => 2.00,
                    'area_name'                      => 'مدينة نصر',
                ],
            ],
            [
                'user' => [
                    'name'     => 'سارة',
                    'email'    => 'sara.print@test.com',
                    'phone'    => '01000000002',
                    'password' => Hash::make('password'),
                    'type'     => UserTypeEnum::PROVIDER->value,
                ],
                'listing' => [
                    'slug'                           => 'print-sara-maadi',
                    'title'                          => 'مركز سارة للطباعة',
                    'description'                    => 'طباعة ليزر وإنكجت، تجليد، لاميناشن.',
                    'is_available'                   => true,
                    'delevery_available'             => false,
                    'has_color_option'               => true,
                    'black_and_white_price_per_page' => 0.40,
                    'color_price_per_page'           => 1.75,
                    'area_name'                      => 'المعادي',
                ],
            ],
            [
                'user' => [
                    'name'     => 'محمد خالة',
                    'email'    => 'mohamed.print@test.com',
                    'phone'    => '01000000003',
                    'password' => Hash::make('password'),
                    'type'     => UserTypeEnum::PROVIDER->value,
                ],
                'listing' => [
                    'slug'                           => 'print-mohamed-dokki',
                    'title'                          => 'مركز محمد للطباعة',
                    'description'                    => 'طباعة وتصوير، خدمة التوصيل للجامعات.',
                    'is_available'                   => true,
                    'delevery_available'             => true,
                    'has_color_option'               => false,
                    'black_and_white_price_per_page' => 0.35,
                    'color_price_per_page'           => 0.00,
                    'area_name'                      => 'الدقي',
                ],
            ],
        ];

        foreach ($providersData as $data) {
            $user = User::firstOrCreate(
                ['email' => $data['user']['email']],
                $data['user']
            );

            $provider = Provider::firstOrCreate(['user_id' => $user->id]);

            $area = Area::where('name', $data['listing']['area_name'])->first();

            if (!$area) {
                $this->command->warn("⚠️  منطقة '{$data['listing']['area_name']}' غير موجودة، تأكد من تشغيل AreaSeeder أولاً.");
                continue;
            }

            PrintService::firstOrCreate(
                ['title' => $data['listing']['title']],
                [
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
        }
    }
}
