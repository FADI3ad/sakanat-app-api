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

    /**
     * Test provider can add multiple services of the same type.
     */
    public function test_provider_can_add_multiple_services_of_same_type()
    {
        $user = User::create([
            'name'     => 'Food Provider',
            'email'    => 'foodprovider@test.com',
            'phone'    => '01000000088',
            'password' => bcrypt('password'),
            'type'     => UserTypeEnum::PROVIDER->value,
        ]);

        $area = Area::first();
        $type = Type::first();

        Sanctum::actingAs($user);

        // Add first service
        $res1 = $this->postJson('/api/v1/services', [
            'title'              => 'الوجبة الأولى',
            'description'        => 'وجبة مشويات',
            'delevery_available' => true,
            'is_available'       => true,
            'price'              => 100.00,
            'area_id'            => $area->id,
            'type_id'            => $type->id,
        ]);
        $res1->assertStatus(201);

        // Add second service of the SAME type
        $res2 = $this->postJson('/api/v1/services', [
            'title'              => 'الوجبة الثانية',
            'description'        => 'وجبة أسماك',
            'delevery_available' => true,
            'is_available'       => true,
            'price'              => 150.00,
            'area_id'            => $area->id,
            'type_id'            => $type->id,
        ]);
        $res2->assertStatus(201);

        $this->assertDatabaseCount('services', 2);
    }

    /**
     * Test provider cannot add service of a different type.
     */
    public function test_provider_cannot_add_service_of_different_type()
    {
        $types = Type::take(2)->get();
        if ($types->count() < 2) {
            $type2 = Type::create([
                'name'        => 'نوع ثاني',
                'description' => 'وصف النوع الثاني',
                'status'      => true,
            ]);
            $types = Type::all();
        }

        $type1 = $types[0];
        $type2 = $types[1];

        $user = User::create([
            'name'     => 'Single Type Provider',
            'email'    => 'singletype@test.com',
            'phone'    => '01000000077',
            'password' => bcrypt('password'),
            'type'     => UserTypeEnum::PROVIDER->value,
        ]);

        $area = Area::first();

        Sanctum::actingAs($user);

        // Add first service under type 1
        $this->postJson('/api/v1/services', [
            'title'              => 'خدمة النوع الأول',
            'delevery_available' => true,
            'price'              => 50.00,
            'area_id'            => $area->id,
            'type_id'            => $type1->id,
        ])->assertStatus(201);

        // Attempt to add service under type 2 (different type) -> Should fail
        $response = $this->postJson('/api/v1/services', [
            'title'              => 'خدمة النوع الثاني المحظورة',
            'delevery_available' => true,
            'price'              => 80.00,
            'area_id'            => $area->id,
            'type_id'            => $type2->id,
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'status'  => false,
                'message' => 'لا يمكنك إضافة خدمة بنوع مختلف، جميع خدماتك يجب أن تكون من نفس النوع (تخصص واحد فقط).',
            ]);
    }

    /**
     * Test getting services belonging to a specific user.
     */
    public function test_can_get_services_by_user_id()
    {
        $user = User::create([
            'name'     => 'Provider User Services',
            'email'    => 'user_services@test.com',
            'phone'    => '01000000066',
            'password' => bcrypt('password'),
            'type'     => UserTypeEnum::PROVIDER->value,
        ]);

        $provider = Provider::create(['user_id' => $user->id]);
        $area = Area::first();
        $type = Type::first();

        Service::create([
            'title'              => 'خدمة المستخدم 1',
            'price'              => 30.00,
            'delevery_available' => true,
            'is_available'       => true,
            'provider_id'        => $provider->id,
            'area_id'            => $area->id,
            'type_id'            => $type->id,
        ]);

        $response = $this->getJson("/api/v1/users/{$user->id}/services");

        $response->assertStatus(200)
            ->assertJson([
                'status'  => true,
                'message' => 'تم استرجاع خدمات المستخدم بنجاح',
                'user'    => [
                    'id'   => $user->id,
                    'name' => 'Provider User Services',
                ],
            ])
            ->assertJsonCount(1, 'data');
    }

    /**
     * Test getting services for a non-provider user returns 400.
     */
    public function test_get_services_by_user_id_fails_for_non_provider()
    {
        $user = User::create([
            'name'     => 'Resident User Services',
            'email'    => 'resident_services@test.com',
            'phone'    => '01000000055',
            'password' => bcrypt('password'),
            'type'     => UserTypeEnum::RESIDENT->value,
        ]);

        $response = $this->getJson("/api/v1/users/{$user->id}/services");

        $response->assertStatus(400)
            ->assertJson([
                'status'  => false,
                'message' => 'المستخدم ليس مقدم خدمة.',
            ]);
    }

    /**
     * Test getting services for a provider with no services returns 400.
     */
    public function test_get_services_by_user_id_fails_for_provider_without_services()
    {
        $user = User::create([
            'name'     => 'Provider Without Services',
            'email'    => 'no_services@test.com',
            'phone'    => '01000000044',
            'password' => bcrypt('password'),
            'type'     => UserTypeEnum::PROVIDER->value,
        ]);

        Provider::create(['user_id' => $user->id]);

        $response = $this->getJson("/api/v1/users/{$user->id}/services");

        $response->assertStatus(400)
            ->assertJson([
                'status'  => false,
                'message' => 'لا توجد خدمات لهذا المستخدم.',
            ]);
    }
}
