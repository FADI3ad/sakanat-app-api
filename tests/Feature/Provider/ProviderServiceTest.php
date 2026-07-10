<?php

namespace Tests\Feature\Provider;

use App\Models\Area;
use App\Models\Service;
use App\Models\Provider;
use App\Models\Type;
use App\Models\User;
use App\Enums\UserTypeEnum;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ProviderServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\TypeSeeder::class);
        $this->seed(\Database\Seeders\AreaSeeder::class);
    }

    /**
     * Test guest cannot add a service.
     */
    public function test_guest_cannot_add_service()
    {
        $response = $this->postJson('/api/v1/services', [
            'title'              => 'خدمة تجريبية',
            'delevery_available' => true,
            'price'              => 50.00,
            'area_id'            => 1,
            'type_id'            => 1,
        ]);

        $response->assertStatus(401);
    }

    /**
     * Test non-provider cannot add a service.
     */
    public function test_non_provider_cannot_add_service()
    {
        $user = User::create([
            'name'     => 'Resident User',
            'email'    => 'resident@test.com',
            'phone'    => '01000000008',
            'password' => bcrypt('password'),
            'type'     => UserTypeEnum::RESIDENT->value,
        ]);

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/services', [
            'title'              => 'خدمة تجريبية',
            'delevery_available' => true,
            'price'              => 50.00,
            'area_id'            => 1,
            'type_id'            => 1,
        ]);

        $response->assertStatus(403)
            ->assertJson([
                'status'  => false,
                'message' => 'غير مصرح لك بالوصول، يجب أن تكون مقدم خدمة.',
            ]);
    }

    /**
     * Test provider can successfully add a service.
     */
    public function test_provider_can_add_service()
    {
        $user = User::create([
            'name'     => 'Provider User',
            'email'    => 'provider@test.com',
            'phone'    => '01000000009',
            'password' => bcrypt('password'),
            'type'     => UserTypeEnum::PROVIDER->value,
        ]);

        $area = Area::first();
        $type = Type::first();

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/services', [
            'title'              => 'خدمة طباعة سريعة ورخيصة',
            'description'        => 'نقدم خدمات الطباعة بجودة عالية.',
            'delevery_available' => true,
            'is_available'       => true,
            'price'              => 45.50,
            'area_id'            => $area->id,
            'type_id'            => $type->id,
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'status'  => true,
                'message' => 'تم إضافة الخدمة بنجاح',
                'data'    => [
                    'title'              => 'خدمة طباعة سريعة ورخيصة',
                    'description'        => 'نقدم خدمات الطباعة بجودة عالية.',
                    'price'              => '45.50',
                    'area'               => $area->name,
                    'type'               => $type->name,
                    'delivery_available' => true,
                    'is_available'       => true,
                ]
            ]);

        $this->assertDatabaseHas('services', [
            'title'              => 'خدمة طباعة سريعة ورخيصة',
            'price'              => 45.50,
            'area_id'            => $area->id,
            'type_id'            => $type->id,
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

        $response = $this->postJson('/api/v1/services', []);

        $response->assertStatus(422)
            ->assertJson([
                'status'  => false,
                'message' => 'بيانات المدخلات غير صالحة.',
            ])
            ->assertJsonValidationErrors([
                'title',
                'delevery_available',
                'price',
                'area_id',
                'type_id',
            ]);
    }

    /**
     * Test provider can update their own service.
     */
    public function test_provider_can_update_own_service()
    {
        $user = User::create([
            'name'     => 'Provider User',
            'email'    => 'provider@test.com',
            'phone'    => '01000000009',
            'password' => bcrypt('password'),
            'type'     => UserTypeEnum::PROVIDER->value,
        ]);

        $provider = Provider::create(['user_id' => $user->id]);
        $area = Area::first();
        $type = Type::first();

        $service = Service::create([
            'title'              => 'الخدمة القديمة',
            'description'        => 'وصف قديم',
            'price'              => 10.00,
            'delevery_available' => false,
            'is_available'       => true,
            'provider_id'        => $provider->id,
            'area_id'            => $area->id,
            'type_id'            => $type->id,
        ]);

        Sanctum::actingAs($user);

        $response = $this->putJson("/api/v1/services/{$service->id}", [
            'title' => 'الخدمة الجديدة المعدلة',
            'price' => 15.75,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'status'  => true,
                'message' => 'تم تحديث الخدمة بنجاح',
                'data'    => [
                    'title' => 'الخدمة الجديدة المعدلة',
                    'price' => '15.75',
                ]
            ]);

        $this->assertDatabaseHas('services', [
            'id'    => $service->id,
            'title' => 'الخدمة الجديدة المعدلة',
            'price' => 15.75,
        ]);
    }

    /**
     * Test provider cannot update someone else's service.
     */
    public function test_provider_cannot_update_other_service()
    {
        $user1 = User::create([
            'name'     => 'Provider 1',
            'email'    => 'provider1@test.com',
            'phone'    => '01000000009',
            'password' => bcrypt('password'),
            'type'     => UserTypeEnum::PROVIDER->value,
        ]);
        $provider1 = Provider::create(['user_id' => $user1->id]);

        $user2 = User::create([
            'name'     => 'Provider 2',
            'email'    => 'provider2@test.com',
            'phone'    => '01000000007',
            'password' => bcrypt('password'),
            'type'     => UserTypeEnum::PROVIDER->value,
        ]);

        $area = Area::first();
        $type = Type::first();

        $service = Service::create([
            'title'              => 'خدمة مقدم 1',
            'price'              => 10.00,
            'delevery_available' => false,
            'is_available'       => true,
            'provider_id'        => $provider1->id,
            'area_id'            => $area->id,
            'type_id'            => $type->id,
        ]);

        Sanctum::actingAs($user2);

        $response = $this->putJson("/api/v1/services/{$service->id}", [
            'title' => 'محاولة تعديل غير مصرحة',
        ]);

        $response->assertStatus(403)
            ->assertJson([
                'status'  => false,
                'message' => 'غير مصرح لك بتعديل هذه الخدمة.',
            ]);
    }
}
