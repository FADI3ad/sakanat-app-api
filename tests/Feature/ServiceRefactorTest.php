<?php

namespace Tests\Feature;

use App\Models\Service;
use App\Models\Type;
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
                    ]
                ],
                'meta' => [
                    'total',
                    'per_page',
                    'current_page',
                    'last_page',
                ]
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
                'status'  => true,
                'message' => 'تم استرجاع تفاصيل نوع الخدمة بنجاح',
                'data'    => [
                    'id'          => $type->id,
                    'name'        => $type->name,
                    'description' => $type->description,
                    'sort_order'  => $type->sort_order,
                    'status'      => (bool) $type->status,
                    'icon'        => $type->icon,
                ]
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
                            'name',
                            'phone',
                        ]
                    ]
                ],
                'meta' => [
                    'total',
                    'per_page',
                    'current_page',
                    'last_page',
                ]
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
                        'name',
                        'phone',
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
                    ]
                ],
                'meta' => ['total', 'per_page', 'current_page', 'last_page'],
            ])
            ->assertJson([
                'status' => true,
                'type'   => [
                    'id'   => $type->id,
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
}
