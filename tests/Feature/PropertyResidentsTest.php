<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Property;
use App\Models\Room;
use App\Models\Bed;
use App\Enums\UserTypeEnum;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PropertyResidentsTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that the property owner can successfully list residents.
     */
    public function test_owner_can_list_residents()
    {
        $owner = User::factory()->create([
            'type' => UserTypeEnum::PROPERTY_OWNER,
        ]);

        $student = User::factory()->create([
            'type' => UserTypeEnum::RESIDENT,
        ]);

        $property = Property::create([
            'user_id' => $owner->id,
            'title'   => 'سكن النخبة',
            'city'    => 'القاهرة',
        ]);

        $room = Room::create([
            'property_id' => $property->id,
            'name'        => 'غرفة 101',
        ]);

        $bed = Bed::create([
            'room_id'       => $room->id,
            'user_id'       => $student->id,
            'occupant_name' => $student->name,
        ]);

        Sanctum::actingAs($owner);

        $response = $this->getJson("/api/v1/properties/{$property->id}/residents");

        $response->assertStatus(200)
            ->assertJson([
                'status'  => true,
                'message' => 'تم استرجاع قائمة الطلاب المقيمين بنجاح',
            ])
            ->assertJsonStructure([
                'status',
                'message',
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'email',
                        'phone',
                        'room' => [
                            'id',
                            'name',
                        ],
                        'bed' => [
                            'id',
                            'occupant_name',
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

        $this->assertCount(1, $response->json('data'));
        $this->assertEquals($student->id, $response->json('data.0.id'));
        $this->assertEquals($room->id, $response->json('data.0.room.id'));
        $this->assertEquals($bed->id, $response->json('data.0.bed.id'));
    }

    /**
     * Test that an admin can list residents for any property.
     */
    public function test_admin_can_list_residents()
    {
        $owner = User::factory()->create([
            'type' => UserTypeEnum::PROPERTY_OWNER,
        ]);

        $admin = User::factory()->create([
            'type' => UserTypeEnum::ADMIN,
        ]);

        $student = User::factory()->create([
            'type' => UserTypeEnum::RESIDENT,
        ]);

        $property = Property::create([
            'user_id' => $owner->id,
            'title'   => 'سكن النخبة',
            'city'    => 'القاهرة',
        ]);

        $room = Room::create([
            'property_id' => $property->id,
            'name'        => 'غرفة 101',
        ]);

        $bed = Bed::create([
            'room_id'       => $room->id,
            'user_id'       => $student->id,
            'occupant_name' => $student->name,
        ]);

        Sanctum::actingAs($admin);

        $response = $this->getJson("/api/v1/properties/{$property->id}/residents");

        $response->assertStatus(200)
            ->assertJson([
                'status'  => true,
            ]);

        $this->assertCount(1, $response->json('data'));
    }

    /**
     * Test that an owner cannot list residents of another owner's property.
     */
    public function test_other_owner_cannot_list_residents()
    {
        $owner1 = User::factory()->create([
            'type' => UserTypeEnum::PROPERTY_OWNER,
        ]);

        $owner2 = User::factory()->create([
            'type' => UserTypeEnum::PROPERTY_OWNER,
        ]);

        $property = Property::create([
            'user_id' => $owner1->id,
            'title'   => 'سكن النخبة',
            'city'    => 'القاهرة',
        ]);

        Sanctum::actingAs($owner2);

        $response = $this->getJson("/api/v1/properties/{$property->id}/residents");

        $response->assertStatus(403)
            ->assertJson([
                'status'  => false,
                'message' => 'غير مصرح لك بعرض طلاب هذا السكن.',
            ]);
    }

    /**
     * Test that residents (students) cannot view the list.
     */
    public function test_student_cannot_list_residents()
    {
        $owner = User::factory()->create([
            'type' => UserTypeEnum::PROPERTY_OWNER,
        ]);

        $student = User::factory()->create([
            'type' => UserTypeEnum::RESIDENT,
        ]);

        $property = Property::create([
            'user_id' => $owner->id,
            'title'   => 'سكن النخبة',
            'city'    => 'القاهرة',
        ]);

        Sanctum::actingAs($student);

        $response = $this->getJson("/api/v1/properties/{$property->id}/residents");

        // The middleware property_owner will abort/redirect/return 403
        $response->assertStatus(403);
    }

    /**
     * Test that guest users are unauthorized.
     */
    public function test_guest_unauthorized()
    {
        $owner = User::factory()->create([
            'type' => UserTypeEnum::PROPERTY_OWNER,
        ]);

        $property = Property::create([
            'user_id' => $owner->id,
            'title'   => 'سكن النخبة',
            'city'    => 'القاهرة',
        ]);

        $response = $this->getJson("/api/v1/properties/{$property->id}/residents");

        $response->assertStatus(401);
    }
}
