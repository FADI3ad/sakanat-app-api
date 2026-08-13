<?php

namespace Tests\Feature;

use App\Enums\UserTypeEnum;
use App\Models\Area;
use App\Models\Provider;
use App\Models\Service;
use App\Models\Type;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ServiceRefactorTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    /**
     * Test list of types.
     */
    public function test_can_list_types()
    {
        $response = $this->getJson('/api/v1/types');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'message',
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'description',
                        'sort_order',
                        'status',
                        'icon',
                    ],
                ],
                'meta' => [
                    'total',
                    'per_page',
                    'current_page',
                    'last_page',
                ],
            ]);
    }

    /**
     * Test show a specific type.
     */
    public function test_can_show_type()
    {
        $type = Type::first();

        $response = $this->getJson("/api/v1/types/{$type->id}");

        $response->assertStatus(200)
            ->assertJson([
                'status' => true,
                'message' => 'تم استرجاع تفاصيل نوع الخدمة بنجاح',
                'data' => [
                    'id' => $type->id,
                    'name' => $type->name,
                    'description' => $type->description,
                    'sort_order' => $type->sort_order,
                    'status' => (bool) $type->status,
                    'icon' => $type->icon,
                ],
            ]);
    }

    /**
     * Test listing of services.
     */
    public function test_can_list_services()
    {
        $response = $this->getJson('/api/v1/services');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'message',
                'data' => [
                    '*' => [
                        'id',
                        'title',
                        'description',
                        'image',
                        'is_available',
                        'delivery_available',
                        'price',
                        'area',
                        'type',
                        'provider' => [
                            'id',
                            'provider_id',
                            'name',
                            'phone',
                        ],
                    ],
                ],
                'meta' => [
                    'total',
                    'per_page',
                    'current_page',
                    'last_page',
                ],
            ]);
    }

    /**
     * Test showing details of a specific service.
     */
    public function test_can_show_service_details()
    {
        $service = Service::first();

        $response = $this->getJson("/api/v1/services/{$service->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'message',
                'data' => [
                    'id',
                    'title',
                    'description',
                    'image',
                    'is_available',
                    'delivery_available',
                    'price',
                    'area',
                    'type',
                    'provider' => [
                        'id',
                        'provider_id',
                        'name',
                        'phone',
                    ],
                ],
            ])
            ->assertJson([
                'data' => [
                    'provider' => [
                        'id' => $service->provider->user_id,
                        'provider_id' => $service->provider->id,
                    ]
                ]
            ]);
    }

    /**
     * Test listing services filtered by a specific type.
     */
    public function test_can_list_services_by_type()
    {
        $type = Type::first();

        $response = $this->getJson("/api/v1/types/{$type->id}/services");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'message',
                'type' => ['id', 'name'],
                'data' => [
                    '*' => [
                        'id',
                        'title',
                        'description',
                        'image',
                        'is_available',
                        'delivery_available',
                        'price',
                        'area',
                        'provider' => ['id', 'name', 'phone'],
                    ],
                ],
                'meta' => ['total', 'per_page', 'current_page', 'last_page'],
            ])
            ->assertJson([
                'status' => true,
                'type' => [
                    'id' => $type->id,
                    'name' => $type->name,
                ],
            ]);

        // All returned services belong to this type
        $data = $response->json('data');
        $typeServiceIds = $type->services()->pluck('id')->toArray();
        foreach ($data as $item) {
            $this->assertContains($item['id'], $typeServiceIds);
        }
    }

    /**
     * Test getting service owner details and other services.
     */
    public function test_can_get_service_owner_details_and_other_services()
    {
        // 1. Create a Provider with user
        $user = User::factory()->create([
            'type' => UserTypeEnum::PROVIDER,
        ]);
        $provider = Provider::create([
            'user_id' => $user->id,
        ]);

        // 2. Query seeded Area and Type
        $area = Area::first();
        $type = Type::first();

        // 3. Create two services for this provider
        $service1 = Service::create([
            'title' => 'Service 1',
            'description' => 'Description 1',
            'price' => 100,
            'is_available' => true,
            'delevery_available' => false,
            'area_id' => $area->id,
            'type_id' => $type->id,
            'provider_id' => $provider->id,
        ]);

        $service2 = Service::create([
            'title' => 'Service 2',
            'description' => 'Description 2',
            'price' => 150,
            'is_available' => true,
            'delevery_available' => true,
            'area_id' => $area->id,
            'type_id' => $type->id,
            'provider_id' => $provider->id,
        ]);

        // 4. Request the owner details of service 1
        $response = $this->getJson("/api/v1/services/{$service1->id}/owner");

        // 5. Assertions
        $response->assertStatus(200)
            ->assertJson([
                'status' => true,
                'message' => 'تم استرجاع بيانات صاحب الخدمة وخدماته الأخرى بنجاح',
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'phone' => $user->phone,
                ],
            ])
            ->assertJsonCount(2, 'data')
            ->assertJsonFragment([
                'id' => $service1->id,
                'title' => 'Service 1',
            ])
            ->assertJsonFragment([
                'id' => $service2->id,
                'title' => 'Service 2',
            ]);
    }
}
