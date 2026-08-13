<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Property;
use App\Models\Room;
use App\Models\Bed;
use App\Models\Absence;
use App\Enums\UserTypeEnum;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;
use Carbon\Carbon;

class PropertyAbsencesTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;
    private User $otherOwner;
    private User $student;
    private User $admin;
    private Property $property;
    private Room $room;
    private Bed $bed;

    protected function setUp(): void
    {
        parent::setUp();

        $this->owner = User::factory()->create([
            'type' => UserTypeEnum::PROPERTY_OWNER,
        ]);

        $this->otherOwner = User::factory()->create([
            'type' => UserTypeEnum::PROPERTY_OWNER,
        ]);

        $this->admin = User::factory()->create([
            'type' => UserTypeEnum::ADMIN,
        ]);

        $this->student = User::factory()->create([
            'type' => UserTypeEnum::RESIDENT,
        ]);

        $this->property = Property::create([
            'user_id' => $this->owner->id,
            'title'   => 'سكن النخبة',
            'city'    => 'القاهرة',
        ]);

        $this->room = Room::create([
            'property_id' => $this->property->id,
            'name'        => 'غرفة 101',
        ]);

        $this->bed = Bed::create([
            'room_id'       => $this->room->id,
            'user_id'       => $this->student->id,
            'occupant_name' => $this->student->name,
        ]);
    }

    /**
     * Test that the property owner can successfully list absences for their own property.
     */
    public function test_owner_can_list_absences_for_own_property()
    {
        Absence::create([
            'user_id'     => $this->student->id,
            'property_id' => $this->property->id,
            'bed_id'      => $this->bed->id,
            'start_date'  => Carbon::today()->toDateString(),
            'end_date'    => Carbon::tomorrow()->toDateString(),
            'reason'      => 'زيارة عائلية',
        ]);

        Sanctum::actingAs($this->owner);

        $response = $this->getJson("/api/v1/properties/{$this->property->id}/absences");

        $response->assertStatus(200)
            ->assertJson([
                'status'  => true,
                'message' => 'تم استرجاع بلاغات الغياب للسكن بنجاح.',
            ])
            ->assertJsonStructure([
                'status',
                'message',
                'data' => [
                    '*' => [
                        'id',
                        'start_date',
                        'end_date',
                        'reason',
                        'is_active',
                        'created_at',
                        'resident' => ['id', 'name', 'email', 'phone'],
                        'property' => ['id', 'title', 'city'],
                        'bed'      => ['id', 'occupant_name', 'room' => ['id', 'name']]
                    ]
                ],
                'meta' => ['total', 'per_page', 'current_page', 'last_page']
            ]);

        $this->assertCount(1, $response->json('data'));
        $this->assertEquals($this->student->id, $response->json('data.0.resident.id'));
        $this->assertEquals('زيارة عائلية', $response->json('data.0.reason'));
    }

    /**
     * Test that the property owner cannot view absences for a property they do not own.
     */
    public function test_owner_cannot_list_absences_for_other_property()
    {
        Sanctum::actingAs($this->otherOwner);

        $response = $this->getJson("/api/v1/properties/{$this->property->id}/absences");

        $response->assertStatus(403)
            ->assertJson([
                'status'  => false,
                'message' => 'غير مصرح لك بعرض بلاغات هذا السكن.',
            ]);
    }

    /**
     * Test that residents cannot view property absences.
     */
    public function test_resident_cannot_list_absences()
    {
        Sanctum::actingAs($this->student);

        $response = $this->getJson("/api/v1/properties/{$this->property->id}/absences");

        $response->assertStatus(403)
            ->assertJson([
                'status'  => false,
                'message' => 'غير مصرح لك بالوصول، يجب أن تكون مالك عقار.',
            ]);
    }

    /**
     * Test that admin can view absences for any property.
     */
    public function test_admin_can_list_absences_for_any_property()
    {
        Absence::create([
            'user_id'     => $this->student->id,
            'property_id' => $this->property->id,
            'bed_id'      => $this->bed->id,
            'start_date'  => Carbon::today()->toDateString(),
            'end_date'    => Carbon::tomorrow()->toDateString(),
            'reason'      => 'زيارة عائلية',
        ]);

        Sanctum::actingAs($this->admin);

        $response = $this->getJson("/api/v1/properties/{$this->property->id}/absences");

        $response->assertStatus(200)
            ->assertJson([
                'status'  => true,
            ]);

        $this->assertCount(1, $response->json('data'));
    }

    /**
     * Test filtering active absences.
     */
    public function test_owner_can_filter_active_absences()
    {
        // Active absence
        Absence::create([
            'user_id'     => $this->student->id,
            'property_id' => $this->property->id,
            'bed_id'      => $this->bed->id,
            'start_date'  => Carbon::today()->toDateString(),
            'end_date'    => Carbon::tomorrow()->toDateString(),
            'reason'      => 'نشط حالياً',
        ]);

        // Inactive absence (past)
        Absence::create([
            'user_id'     => $this->student->id,
            'property_id' => $this->property->id,
            'bed_id'      => $this->bed->id,
            'start_date'  => Carbon::yesterday()->subDays(5)->toDateString(),
            'end_date'    => Carbon::yesterday()->subDays(3)->toDateString(),
            'reason'      => 'غياب قديم',
        ]);

        Sanctum::actingAs($this->owner);

        // Fetching all
        $responseAll = $this->getJson("/api/v1/properties/{$this->property->id}/absences");
        $responseAll->assertStatus(200);
        $this->assertCount(2, $responseAll->json('data'));

        // Fetching active only
        $responseActive = $this->getJson("/api/v1/properties/{$this->property->id}/absences?active=1");
        $responseActive->assertStatus(200);
        $this->assertCount(1, $responseActive->json('data'));
        $this->assertEquals('نشط حالياً', $responseActive->json('data.0.reason'));
    }
}
