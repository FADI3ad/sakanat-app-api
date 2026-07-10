<?php

namespace Tests\Feature;

use App\Models\Area;
use App\Models\PrintService;
use App\Models\Provider;
use App\Models\User;
use App\Enums\UserTypeEnum;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ProviderPrintServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\ServiceSeeder::class);
    }

    /**
     * Test guest cannot add a print service.
     */
    public function test_guest_cannot_add_print_service()
    {
        $response = $this->postJson('/api/v1/print-services', [
            'title'                          => 'مركز طباعة تجريبي',
            'delevery_available'             => true,
            'has_color_option'               => false,
            'black_and_white_price_per_page' => 0.50,
            'area_id'                        => 1,
        ]);

        $response->assertStatus(401);
    }

    /**
     * Test non-provider cannot add a print service.
     */
    public function test_non_provider_cannot_add_print_service()
    {
        $user = User::create([
            'name'     => 'Resident User',
            'email'    => 'resident@test.com',
            'phone'    => '01000000008',
            'password' => bcrypt('password'),
            'type'     => UserTypeEnum::RESIDENT->value,
        ]);

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/print-services', [
            'title'                          => 'مركز طباعة تجريبي',
            'delevery_available'             => true,
            'has_color_option'               => false,
            'black_and_white_price_per_page' => 0.50,
            'area_id'                        => 1,
        ]);

        $response->assertStatus(403)
            ->assertJson([
                'status'  => false,
                'message' => 'غير مصرح لك بالوصول، يجب أن تكون مقدم خدمة.',
            ]);
    }

    /**
     * Test provider can successfully add a print service.
     */
    public function test_provider_can_add_print_service()
    {
        $user = User::create([
            'name'     => 'Provider User',
            'email'    => 'provider@test.com',
            'phone'    => '01000000009',
            'password' => bcrypt('password'),
            'type'     => UserTypeEnum::PROVIDER->value,
        ]);

        $area = Area::create(['name' => 'الدقي']);

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/print-services', [
            'title'                          => 'مركز النيل للطباعة والنسخ',
            'description'                    => 'نقدم جميع خدمات الطباعة السريعة والتجليد.',
            'delevery_available'             => true,
            'has_color_option'               => true,
            'black_and_white_price_per_page' => 0.50,
            'color_price_per_page'           => 2.00,
            'area_id'                        => $area->id,
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'status'  => true,
                'message' => 'تم إضافة خدمة الطباعة بنجاح',
                'data'    => [
                    'title'                          => 'مركز النيل للطباعة والنسخ',
                    'description'                    => 'نقدم جميع خدمات الطباعة السريعة والتجليد.',
                    'area'                           => 'الدقي',
                    'delivery_available'             => true,
                    'has_color_option'               => true,
                    'black_and_white_price_per_page' => '0.50',
                    'color_price_per_page'           => '2.00',
                    'is_available'                   => true,
                ]
            ]);

        $this->assertDatabaseHas('print_services', [
            'title'                          => 'مركز النيل للطباعة والنسخ',
            'delevery_available'             => 1,
            'has_color_option'               => 1,
            'black_and_white_price_per_page' => 0.50,
            'color_price_per_page'           => 2.00,
            'area_id'                        => $area->id,
        ]);
    }

    /**
     * Test validation failure.
     */
    public function test_validation_fails_for_invalid_input()
    {
        $user = User::create([
            'name'     => 'Provider User',
            'email'    => 'provider@test.com',
            'phone'    => '01000000009',
            'password' => bcrypt('password'),
            'type'     => UserTypeEnum::PROVIDER->value,
        ]);

        Sanctum::actingAs($user);

        // Missing required fields
        $response = $this->postJson('/api/v1/print-services', []);

        $response->assertStatus(422)
            ->assertJson([
                'status'  => false,
                'message' => 'بيانات المدخلات غير صالحة.',
            ])
            ->assertJsonValidationErrors([
                'title',
                'delevery_available',
                'has_color_option',
                'black_and_white_price_per_page',
                'area_id',
            ]);
    }
}
