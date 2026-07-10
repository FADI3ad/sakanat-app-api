<?php

namespace Database\Seeders;

use App\Enums\UserTypeEnum;
use App\Models\Area;
use App\Models\Service;
use App\Models\Provider;
use App\Models\Type;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class ServiceSeeder extends Seeder
{
    public function run(): void
    {
        $printType = Type::where('name', 'خدمات الطباعة والتصوير')->first();

        if (!$printType) {
            $this->command->warn('⚠️ لم يتم العثور على نوع الطباعة، تأكد من تشغيل TypeSeeder أولاً.');
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
                    'title'              => 'مركز ابانوب للطباعة',
                    'description'        => 'خدمات طباعة سريعة وعالية الجودة، متاح 24 ساعة.',
                    'is_available'       => true,
                    'delevery_available' => true,
                    'price'              => 15.00,
                    'area_name'          => 'مدينة نصر',
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
                    'title'              => 'مركز سارة للطباعة',
                    'description'        => 'طباعة ليزر وإنكجت، تجليد، لاميناشن.',
                    'is_available'       => true,
                    'delevery_available' => false,
                    'price'              => 12.50,
                    'area_name'          => 'المعادي',
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
                    'title'              => 'مركز محمد للطباعة',
                    'description'        => 'طباعة وتصوير، خدمة التوصيل للجامعات.',
                    'is_available'       => true,
                    'delevery_available' => true,
                    'price'              => 10.00,
                    'area_name'          => 'الدقي',
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
                $this->command->warn("⚠️ منطقة '{$data['listing']['area_name']}' غير موجودة، تأكد من تشغيل AreaSeeder أولاً.");
                continue;
            }

            Service::firstOrCreate(
                ['title' => $data['listing']['title']],
                [
                    'description'        => $data['listing']['description'],
                    'is_available'       => $data['listing']['is_available'],
                    'delevery_available' => $data['listing']['delevery_available'],
                    'price'              => $data['listing']['price'],
                    'provider_id'        => $provider->id,
                    'type_id'            => $printType->id,
                    'area_id'            => $area->id,
                ]
            );
        }
    }
}
