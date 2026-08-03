<?php

namespace App\Services;

use App\Enums\AttendanceStatusEnum;
use App\Models\AttendanceLog;
use App\Models\Bed;
use App\Models\Property;
use Carbon\Carbon;

class AttendanceService
{
    /**
     * حساب المسافة بين نقطتين جغرافيتين بالمتر
     * Haversine Formula
     */
    public function calculateDistance(
        float $lat1,
        float $lng1,
        float $lat2,
        float $lng2
    ): float {
        $earthRadius = 6371000; // نصف قطر الأرض بالمتر

        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);

        $a = sin($dLat / 2) * sin($dLat / 2)
           + cos(deg2rad($lat1)) * cos(deg2rad($lat2))
           * sin($dLng / 2) * sin($dLng / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return round($earthRadius * $c, 2);
    }

    /**
     * تسجيل حضور الطالب بعد مسح QR
     *
     * @return array{success: bool, message: string, data?: array}
     */
    public function checkin(
        Property $property,
        Bed $bed,
        float $studentLat,
        float $studentLng
    ): array {
        $today = Carbon::today()->toDateString();

        // --- 1. التحقق إن السرير ملك هذا السكن ---
        $bed->load('room');
        if ($bed->room->property_id !== $property->id) {
            return [
                'success' => false,
                'message' => 'هذا السرير لا ينتمي لهذا السكن.',
            ];
        }

        // --- 2. التحقق من وجود لوكيشن للسكن ---
        if (is_null($property->latitude) || is_null($property->longitude)) {
            return [
                'success' => false,
                'message' => 'لم يتم تحديد موقع السكن بعد.',
            ];
        }

        // --- 3. حساب المسافة ---
        $distance = $this->calculateDistance(
            $property->latitude,
            $property->longitude,
            $studentLat,
            $studentLng
        );

        // --- 4. التحقق من الجيو-فنس ---
        $allowedRadius = $property->radius ?? 100; // 100 متر افتراضي إذا لم يُحدَّد
        if ($distance > $allowedRadius) {
            return [
                'success'  => false,
                'message'  => 'أنت خارج النطاق المسموح به للسكن.',
                'data'     => [
                    'distance_from_property' => $distance,
                    'allowed_radius'         => $allowedRadius,
                ],
            ];
        }

        // --- 5. تحديد الحالة (present أو late) ---
        $status = AttendanceStatusEnum::PRESENT;

        if ($property->curfew_time) {
            $curfew = Carbon::today()->setTimeFromTimeString($property->curfew_time);
            if (Carbon::now()->greaterThan($curfew)) {
                $status = AttendanceStatusEnum::LATE;
            }
        }

        // --- 6. إنشاء أو تحديث سجل الحضور ---
        $log = AttendanceLog::updateOrCreate(
            [
                'bed_id' => $bed->id,
                'date'   => $today,
            ],
            [
                'property_id'            => $property->id,
                'user_id'                => $bed->user_id,
                'status'                 => $status,
                'checked_in_at'          => Carbon::now(),
                'scanned_latitude'       => $studentLat,
                'scanned_longitude'      => $studentLng,
                'distance_from_property' => $distance,
            ]
        );

        $label = $status === AttendanceStatusEnum::LATE ? 'متأخر' : 'حاضر';

        return [
            'success' => true,
            'message' => "تم تسجيل حضورك بنجاح كـ {$label}",
            'data'    => [
                'status'                 => $status->value,
                'checked_in_at'          => $log->checked_in_at,
                'distance_from_property' => $distance,
                'allowed_radius'         => $allowedRadius,
            ],
        ];
    }

    /**
     * تسجيل جميع المقيمين الغائبين لسكن معين في يوم معين
     * يُستدعى من الـ Cron Job عند انتهاء وقت الكيرفيو
     */
    public function markAbsentsForProperty(Property $property, string $date): int
    {
        $markedCount = 0;

        // جيب كل الأسرة المشغولة (فيها user_id)
        $occupiedBeds = Bed::whereHas('room', fn($q) => $q->where('property_id', $property->id))
            ->whereNotNull('user_id')
            ->get();

        foreach ($occupiedBeds as $bed) {
            // إذا مفيش سجل حضور ليه النهارده → سجل غائب
            $alreadyLogged = AttendanceLog::where('bed_id', $bed->id)
                ->where('date', $date)
                ->exists();

            if (! $alreadyLogged) {
                AttendanceLog::create([
                    'property_id' => $property->id,
                    'bed_id'      => $bed->id,
                    'user_id'     => $bed->user_id,
                    'date'        => $date,
                    'status'      => AttendanceStatusEnum::ABSENT,
                ]);
                $markedCount++;
            }
        }

        return $markedCount;
    }
}
