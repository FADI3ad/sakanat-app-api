<?php

namespace Tests\Feature;

use App\Models\Area;
use App\Models\User;
use App\Models\Service;
use App\Models\Provider;
use App\Models\Type;
use App\Enums\UserTypeEnum;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AreaTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_list_areas()
    {
        Area::create(['name' => 'المعادي']);
        Area::create(['name' => 'مدينة نصر']);

        $response = $this->getJson('/api/v1/areas');

        $response->assertStatus(200)
            ->assertJson([
                'status'  => true,
                'message' => 'تم استرجاع المناطق بنجاح',
            ])
            ->assertJsonCount(2, 'data');
    }

    public function test_can_show_area_details()
    {
        $area = Area::create(['name' => 'الزمالك']);

        $response = $this->getJson("/api/v1/areas/{$area->id}");

        $response->assertStatus(200)
            ->assertJson([
                'status'  => true,
                'message' => 'تم استرجاع تفاصيل المنطقة بنجاح',
                'data'    => [
                    'id'   => $area->id,
                    'name' => 'الزمالك',
                ],
            ]);
    }

    public function test_provider_can_create_area()
    {
        $provider = User::factory()->create(['type' => UserTypeEnum::PROVIDER]);
        Sanctum::actingAs($provider);

        $response = $this->postJson('/api/v1/areas', [
            'name' => 'التجمع الخامس',
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'status'  => true,
                'message' => 'تم إضافة المنطقة بنجاح',
                'data'    => [
                    'name' => 'التجمع الخامس',
                ],
            ]);

        $this->assertDatabaseHas('areas', ['name' => 'التجمع الخامس']);
    }

    public function test_cannot_create_area_with_duplicate_name()
    {
        Area::create(['name' => 'الدقي']);
        $provider = User::factory()->create(['type' => UserTypeEnum::PROVIDER]);
        Sanctum::actingAs($provider);

        $response = $this->postJson('/api/v1/areas', [
            'name' => 'الدقي',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    public function test_provider_can_update_area()
    {
        $area = Area::create(['name' => 'مصر الجديدة القديمة']);
        $provider = User::factory()->create(['type' => UserTypeEnum::PROVIDER]);
        Sanctum::actingAs($provider);

        $response = $this->putJson("/api/v1/areas/{$area->id}", [
            'name' => 'مصر الجديدة',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'status'  => true,
                'message' => 'تم تحديث المنطقة بنجاح',
                'data'    => [
                    'id'   => $area->id,
                    'name' => 'مصر الجديدة',
                ],
            ]);

        $this->assertDatabaseHas('areas', ['id' => $area->id, 'name' => 'مصر الجديدة']);
    }

    public function test_provider_can_delete_area()
    {
        $area = Area::create(['name' => 'شبرا']);
        $provider = User::factory()->create(['type' => UserTypeEnum::PROVIDER]);
        Sanctum::actingAs($provider);

        $response = $this->deleteJson("/api/v1/areas/{$area->id}");

        $response->assertStatus(200)
            ->assertJson([
                'status'  => true,
                'message' => 'تم حذف المنطقة بنجاح',
            ]);

        $this->assertDatabaseMissing('areas', ['id' => $area->id]);
    }

    public function test_can_list_services_in_area()
    {
        $area = Area::create(['name' => 'المهندسين']);
        $type = Type::create(['name' => 'مطعم']);
        $providerUser = User::factory()->create(['type' => UserTypeEnum::PROVIDER]);
        $provider = Provider::create(['user_id' => $providerUser->id]);

        Service::create([
            'provider_id' => $provider->id,
            'area_id'     => $area->id,
            'type_id'     => $type->id,
            'title'       => 'مطعم الشرق',
            'price'       => 100,
        ]);

        $response = $this->getJson("/api/v1/areas/{$area->id}/services");

        $response->assertStatus(200)
            ->assertJson([
                'status'  => true,
                'message' => 'تم استرجاع خدمات المنطقة بنجاح',
                'area'    => [
                    'id'   => $area->id,
                    'name' => 'المهندسين',
                ],
            ])
            ->assertJsonCount(1, 'data');
    }
}
