<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Property;
use App\Enums\UserTypeEnum;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PropertyTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test property_owner can create a property with simplified address.
     */
    public function test_property_owner_can_create_property()
    {
        $owner = User::factory()->create([
            'type' => UserTypeEnum::PROPERTY_OWNER,
        ]);

        Sanctum::actingAs($owner);

        $payload = [
            'title'           => 'شقة سكنية فاخرة',
            'city'            => 'القاهرة',
            'floor'           => '3',
            'address_details' => 'بجوار محطة مترو المعادي',
            'is_available'    => true,
            'description'     => 'سكن مكيف ومؤثث بالكامل للايجار المشترك',
        ];

        $response = $this->postJson('/api/v1/properties', $payload);

         $response->assertStatus(201)
             ->assertJson([
                 'status'  => true,
                 'message' => 'تم إضافة السكن بنجاح',
             ])
             ->assertJsonStructure([
                 'status',
                 'message',
                 'data' => [
                     'id',
                     'title',
                     'city',
                     'floor',
                     'address_details',
                     'latitude',
                     'longitude',
                     'radius',
                     'is_available',
                     'description',
                 ]
             ]);

         $this->assertDatabaseHas('properties', [
             'user_id'   => $owner->id,
             'title'     => 'شقة سكنية فاخرة',
             'city'      => 'القاهرة',
             'latitude'  => null,
             'longitude' => null,
             'radius'    => null,
         ]);
     }

     /**
      * Test property_owner can create a property with location.
      */
     public function test_property_owner_can_create_property_with_location()
     {
         $owner = User::factory()->create([
             'type' => UserTypeEnum::PROPERTY_OWNER,
         ]);

         Sanctum::actingAs($owner);

         $payload = [
             'title'           => 'شقة سكنية بموقع',
             'city'            => 'القاهرة',
             'floor'           => '3',
             'address_details' => 'المعادي',
             'latitude'        => 30.013056,
             'longitude'       => 31.208853,
             'radius'          => 500,
             'is_available'    => true,
             'description'     => 'وصف تفصيلي',
         ];

         $response = $this->postJson('/api/v1/properties', $payload);

         $response->assertStatus(201)
             ->assertJsonPath('data.latitude', 30.013056)
             ->assertJsonPath('data.longitude', 31.208853)
             ->assertJsonPath('data.radius', 500);

         $this->assertDatabaseHas('properties', [
             'user_id'   => $owner->id,
             'title'     => 'شقة سكنية بموقع',
             'latitude'  => 30.013056,
             'longitude' => 31.208853,
             'radius'    => 500,
         ]);
     }

     /**
      * Test property_owner cannot create a property with invalid location.
      */
     public function test_property_owner_cannot_create_property_with_invalid_location()
     {
         $owner = User::factory()->create([
             'type' => UserTypeEnum::PROPERTY_OWNER,
         ]);

         Sanctum::actingAs($owner);

         $payload = [
             'title'     => 'شقة سكنية بموقع خاطئ',
             'city'      => 'القاهرة',
             'latitude'  => 100, // Invalid latitude (> 90)
             'longitude' => 200, // Invalid longitude (> 180)
             'radius'    => -5,   // Invalid radius (< 0)
         ];

         $response = $this->postJson('/api/v1/properties', $payload);

         $response->assertStatus(422)
             ->assertJsonValidationErrors(['latitude', 'longitude', 'radius']);
     }

     /**
      * Test property_owner cannot create multiple properties.
      */
     public function test_property_owner_cannot_create_multiple_properties()
     {
         $owner = User::factory()->create([
             'type' => UserTypeEnum::PROPERTY_OWNER,
         ]);

         Sanctum::actingAs($owner);

         // First creation
         Property::create([
             'user_id' => $owner->id,
             'title'   => 'العقار الأول',
             'city'    => 'القاهرة',
         ]);

         // Second creation via api
         $payload = [
             'title'           => 'العقار الثاني',
             'city'            => 'الجيزة',
             'floor'           => '1',
         ];

         $response = $this->postJson('/api/v1/properties', $payload);

         $response->assertStatus(409)
             ->assertJson([
                 'status'  => false,
                 'message' => 'لقد قمت بإضافة سكن بالفعل، لا يمكنك إضافة أكثر من سكن واحد.',
             ]);
     }

     /**
      * Test property_owner can get QR data of own property.
      */
     public function test_property_owner_can_get_qr_data()
     {
         $owner = User::factory()->create([
             'type' => UserTypeEnum::PROPERTY_OWNER,
         ]);

         Sanctum::actingAs($owner);

         $property = Property::create([
             'user_id'         => $owner->id,
             'title'           => 'شقة المعادي',
             'city'            => 'القاهرة',
             'floor'           => '5',
             'address_details' => 'شارع 9 المعادي',
             'latitude'        => 29.9602,
             'longitude'       => 31.2569,
         ]);

         $response = $this->getJson("/api/v1/properties/{$property->id}/qr-data");

         $response->assertStatus(200)
             ->assertJson([
                 'status'  => true,
                 'message' => 'تم استرجاع بيانات الـ QR كود بنجاح',
                 'data'    => [
                     'owner_id'        => $owner->id,
                     'owner_name'      => $owner->name,
                     'city'            => 'القاهرة',
                     'floor'           => '5',
                     'address_details' => 'شارع 9 المعادي',
                     'latitude'        => 29.9602,
                     'longitude'       => 31.2569,
                 ]
             ]);
     }

     /**
      * Test property_owner cannot get QR data of other properties.
      */
     public function test_property_owner_cannot_get_qr_data_of_others()
     {
         $owner1 = User::factory()->create(['type' => UserTypeEnum::PROPERTY_OWNER]);
         $owner2 = User::factory()->create(['type' => UserTypeEnum::PROPERTY_OWNER]);

         $property = Property::create([
             'user_id' => $owner2->id,
             'title'   => 'سكن المالك الثاني',
             'city'    => 'الجيزة',
         ]);

         Sanctum::actingAs($owner1);

         $response = $this->getJson("/api/v1/properties/{$property->id}/qr-data");

         $response->assertStatus(403)
             ->assertJson([
                 'status'  => false,
                 'message' => 'غير مصرح لك بعرض هذا السكن.',
             ]);
     }

    /**
     * Test property_owner can list only their own properties.
     */
    public function test_property_owner_can_list_own_properties()
    {
        $owner1 = User::factory()->create(['type' => UserTypeEnum::PROPERTY_OWNER]);
        $owner2 = User::factory()->create(['type' => UserTypeEnum::PROPERTY_OWNER]);

        // Property for owner 1
        Property::create([
            'user_id' => $owner1->id,
            'title'   => 'سكن المالك الأول',
            'city'    => 'القاهرة',
        ]);

        // Property for owner 2
        Property::create([
            'user_id' => $owner2->id,
            'title'   => 'سكن المالك الثاني',
            'city'    => 'الجيزة',
        ]);

        Sanctum::actingAs($owner1);

        $response = $this->getJson('/api/v1/properties/my');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title', 'سكن المالك الأول');
    }

    /**
     * Test property_owner can update own property.
     */
    public function test_property_owner_can_update_own_property()
    {
        $owner = User::factory()->create(['type' => UserTypeEnum::PROPERTY_OWNER]);
        $property = Property::create([
            'user_id' => $owner->id,
            'title'   => 'العنوان القديم',
            'city'    => 'القاهرة',
        ]);

        Sanctum::actingAs($owner);

        $response = $this->putJson("/api/v1/properties/{$property->id}", [
            'title' => 'العنوان الجديد',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.title', 'العنوان الجديد');

        $this->assertDatabaseHas('properties', [
            'id'    => $property->id,
            'title' => 'العنوان الجديد',
        ]);
    }

    /**
     * Test property_owner can update own property with location.
     */
    public function test_property_owner_can_update_own_property_with_location()
    {
        $owner = User::factory()->create(['type' => UserTypeEnum::PROPERTY_OWNER]);
        $property = Property::create([
            'user_id' => $owner->id,
            'title'   => 'العنوان القديم',
            'city'    => 'القاهرة',
        ]);

        Sanctum::actingAs($owner);

        $response = $this->putJson("/api/v1/properties/{$property->id}", [
            'latitude'  => 30.013056,
            'longitude' => 31.208853,
            'radius'    => 500,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.latitude', 30.013056)
            ->assertJsonPath('data.longitude', 31.208853)
            ->assertJsonPath('data.radius', 500);

        $this->assertDatabaseHas('properties', [
            'id'        => $property->id,
            'latitude'  => 30.013056,
            'longitude' => 31.208853,
            'radius'    => 500,
        ]);
    }

    /**
     * Test user with wrong type cannot access property owner endpoints.
     */
    public function test_resident_cannot_create_property()
    {
        $resident = User::factory()->create([
            'type' => UserTypeEnum::RESIDENT,
        ]);

        Sanctum::actingAs($resident);

        $response = $this->postJson('/api/v1/properties', [
            'title' => 'شقة سكنية',
            'city'  => 'القاهرة',
        ]);

        $response->assertStatus(403)
            ->assertJson([
                'status'  => false,
                'message' => 'غير مصرح لك بالوصول، يجب أن تكون مالك عقار.',
            ]);
    }

    /**
     * Test admin can view and delete properties.
     */
    public function test_admin_can_manage_properties()
    {
        $owner = User::factory()->create(['type' => UserTypeEnum::PROPERTY_OWNER]);
        $admin = User::factory()->create(['type' => UserTypeEnum::ADMIN]);

        $property = Property::create([
            'user_id' => $owner->id,
            'title'   => 'سكن للاختبار الإداري',
            'city'    => 'القاهرة',
        ]);

        Sanctum::actingAs($admin);

        // List all properties as admin
        $responseList = $this->getJson('/api/v1/admin/properties');
        $responseList->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'message',
                'data' => [
                    '*' => [
                        'id',
                        'title',
                        'owner' => ['id', 'name', 'email', 'phone']
                    ]
                ]
            ]);

        // Show specific property as admin
        $responseShow = $this->getJson("/api/v1/admin/properties/{$property->id}");
        $responseShow->assertStatus(200)
            ->assertJsonPath('data.title', 'سكن للاختبار الإداري');

        // Delete property as admin
        $responseDelete = $this->deleteJson("/api/v1/admin/properties/{$property->id}");
        $responseDelete->assertStatus(200);

        $this->assertDatabaseMissing('properties', [
            'id' => $property->id,
        ]);
    }
}
