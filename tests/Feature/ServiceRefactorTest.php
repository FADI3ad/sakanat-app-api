<?php

namespace Tests\Feature;

use App\Models\PrintService;
use App\Models\Service;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ServiceRefactorTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Seed the database
        $this->seed(DatabaseSeeder::class);
    }

    /**
     * Test list of base services.
     */
    public function test_can_list_base_services()
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
     * Test show a specific base service.
     */
    public function test_can_show_base_service()
    {
        $service = Service::first();

        $response = $this->getJson("/api/v1/services/{$service->id}");

        $response->assertStatus(200)
            ->assertJson([
                'status'  => true,
                'message' => 'تم استرجاع الخدمة بنجاح',
                'data'    => [
                    'id'          => $service->id,
                    'title'       => $service->title,
                    'description' => $service->description,
                    'icon'        => $service->icon,
                    'status'      => (int) $service->status,
                ]
            ]);
    }

    /**
     * Test listing of print services.
     */
    public function test_can_list_print_services()
    {
        $response = $this->getJson('/api/v1/print-services');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'message',
                'service' => [
                    'id',
                    'title',
                ],
                'data' => [
                    '*' => [
                        'id',
                        'provider_name',
                        'phone',
                        'title',
                        'image',
                        'area',
                        'delivery_available',
                        'has_color_option',
                        'black_and_white_price_per_page',
                        'color_price_per_page',
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
     * Test showing details of a specific print service.
     */
    public function test_can_show_print_service_details()
    {
        $printService = PrintService::first();

        $response = $this->getJson("/api/v1/print-services/{$printService->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'message',
                'service' => [
                    'id',
                    'title',
                ],
                'data' => [
                    'id',
                    'title',
                    'description',
                    'image',
                    'area',
                    'delivery_available',
                    'has_color_option',
                    'black_and_white_price_per_page',
                    'color_price_per_page',
                    'is_available',
                    'provider' => [
                        'id',
                        'name',
                        'phone',
                        'email',
                        'address',
                    ]
                ]
            ]);
    }

    /**
     * Test nested service listings.
     */
    public function test_can_get_nested_service_listings()
    {
        $service = Service::first(); // ID 1

        $response = $this->getJson("/api/v1/services/{$service->id}/listings");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'message',
                'service' => [
                    'id',
                    'title',
                ],
                'data' => [
                    '*' => [
                        'id',
                        'provider_name',
                        'phone',
                        'title',
                        'image',
                        'area',
                        'delivery_available',
                        'has_color_option',
                        'black_and_white_price_per_page',
                        'color_price_per_page',
                    ]
                ]
            ]);
    }

    /**
     * Test nested service listing details.
     */
    public function test_can_get_nested_service_listing_details()
    {
        $service = Service::first(); // ID 1
        $printService = PrintService::first();

        $response = $this->getJson("/api/v1/services/{$service->id}/listings/{$printService->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'message',
                'service' => [
                    'id',
                    'title',
                ],
                'data' => [
                    'id',
                    'title',
                    'description',
                    'image',
                    'area',
                    'delivery_available',
                    'has_color_option',
                    'black_and_white_price_per_page',
                    'color_price_per_page',
                    'is_available',
                    'provider' => [
                        'id',
                        'name',
                        'phone',
                        'email',
                        'address',
                    ]
                ]
            ]);
    }
}
