<?php

namespace Tests\Feature;

use App\Enums\AttendanceStatusEnum;
use App\Enums\UserTypeEnum;
use App\Models\AttendanceLog;
use App\Models\Bed;
use App\Models\Property;
use App\Models\Room;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AttendanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_property_owner_can_get_detailed_attendance_logs()
    {
        $owner = User::factory()->create([
            'type'  => UserTypeEnum::PROPERTY_OWNER,
            'phone' => '01000000001',
        ]);

        $student = User::factory()->create([
            'name'       => 'أحمد محمود',
            'email'      => 'ahmed@example.com',
            'phone'      => '01111111111',
            'type'       => UserTypeEnum::RESIDENT,
            'is_blocked' => false,
        ]);

        $property = Property::create([
            'user_id'     => $owner->id,
            'title'       => 'سكن الطلاب الممتاز',
            'city'        => 'القاهرة',
            'curfew_time' => '22:00:00',
        ]);

        $room = Room::create([
            'property_id' => $property->id,
            'name'        => 'غرفة 101',
            'description' => 'غرفة تطل على الشارع الرئيسي',
        ]);

        $bed = Bed::create([
            'room_id'       => $room->id,
            'user_id'       => $student->id,
            'occupant_name' => 'أحمد محمود',
        ]);

        $log = AttendanceLog::create([
            'property_id'            => $property->id,
            'bed_id'                 => $bed->id,
            'user_id'                => $student->id,
            'date'                   => '2026-08-04',
            'status'                 => AttendanceStatusEnum::PRESENT,
            'checked_in_at'          => '2026-08-04 20:30:00',
            'scanned_latitude'       => 30.0444,
            'scanned_longitude'      => 31.2357,
            'distance_from_property' => 15.5,
        ]);

        Sanctum::actingAs($owner);

        // 1. General logs
        $response = $this->getJson("/api/v1/properties/{$property->id}/attendance");
        $response->assertStatus(200)->assertJson(['status' => true]);

        // 2. Daily logs endpoint
        $dailyResponse = $this->getJson("/api/v1/properties/{$property->id}/attendance/daily?date=2026-08-04");
        $dailyResponse->assertStatus(200)
            ->assertJson([
                'status'  => true,
                'message' => 'تم استرجاع سجل الحضور اليومي بنجاح',
                'date'    => '2026-08-04',
                'summary' => [
                    'total_residents' => 1,
                    'present_count'   => 1,
                    'late_count'      => 0,
                    'absent_count'    => 0,
                ],
            ]);

        // 3. Monthly logs endpoint
        $monthlyResponse = $this->getJson("/api/v1/properties/{$property->id}/attendance/monthly?month=2026-08");
        $monthlyResponse->assertStatus(200)
            ->assertJson([
                'status'  => true,
                'message' => 'تم استرجاع سجل الحضور الشهري بنجاح',
                'month'   => '2026-08',
                'summary' => [
                    'total_records'   => 1,
                    'present_count'   => 1,
                    'late_count'      => 0,
                    'absent_count'    => 0,
                    'attendance_rate' => 100,
                ],
            ]);
    }

    public function test_checkin_detects_early_and_30_min_late_correctly()
    {
        $owner = User::factory()->create(['type' => UserTypeEnum::PROPERTY_OWNER]);
        $student = User::factory()->create(['type' => UserTypeEnum::RESIDENT]);

        $property = Property::create([
            'user_id'     => $owner->id,
            'title'       => 'سكن التجربة',
            'city'        => 'القاهرة',
            'latitude'    => 30.0444,
            'longitude'   => 31.2357,
            'radius'      => 100,
            'curfew_time' => '22:00:00',
        ]);

        $room = Room::create(['property_id' => $property->id, 'name' => 'غرفة 1']);
        $bed  = Bed::create(['room_id' => $room->id, 'user_id' => $student->id, 'occupant_name' => 'طالب']);

        Sanctum::actingAs($student);

        // Test Early Arrival (e.g. 21:00)
        Carbon::setTestNow('2026-08-04 21:00:00');
        $earlyResp = $this->postJson('/api/v1/attendance/checkin', [
            'property_id' => $property->id,
            'bed_id'      => $bed->id,
            'latitude'    => 30.0444,
            'longitude'   => 31.2357,
        ]);

        $earlyResp->assertStatus(200)
            ->assertJson([
                'status' => true,
                'data'   => [
                    'status'             => 'present',
                    'is_early'           => true,
                    'is_late'            => false,
                    'minutes_difference' => 60,
                    'time_remark'        => 'early',
                ],
            ]);
        $this->assertStringContainsString('جاي بدري', $earlyResp->json('message'));

        // Test Late Arrival by 35 mins (e.g. 22:35)
        Carbon::setTestNow('2026-08-04 22:35:00');
        $lateResp = $this->postJson('/api/v1/attendance/checkin', [
            'property_id' => $property->id,
            'bed_id'      => $bed->id,
            'latitude'    => 30.0444,
            'longitude'   => 31.2357,
        ]);

        $lateResp->assertStatus(200)
            ->assertJson([
                'status' => true,
                'data'   => [
                    'status'             => 'late',
                    'is_early'           => false,
                    'is_late'            => true,
                    'minutes_difference' => 35,
                    'time_remark'        => 'late',
                ],
            ]);
        $this->assertStringContainsString('تأخير', $lateResp->json('message'));

        Carbon::setTestNow(); // Reset time
    }
}
