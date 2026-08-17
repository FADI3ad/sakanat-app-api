<?php

namespace App\Http\Controllers;

use App\Enums\AttendanceStatusEnum;
use App\Http\Requests\Attendance\CheckinRequest;
use App\Http\Requests\Attendance\UpdateCurfewRequest;
use App\Models\AttendanceLog;
use App\Models\Bed;
use App\Models\Property;
use App\Services\AttendanceService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class AttendanceController extends Controller
{
    public function __construct(
        private readonly AttendanceService $attendanceService
    ) {}

    /*
    |--------------------------------------------------------------------------
    | Helper: Format a single attendance log for API response
    |--------------------------------------------------------------------------
    */
    private function formatLog(AttendanceLog $log): array
    {
        return [
            'id'                     => $log->id,
            'date'                   => $log->date?->toDateString(),
            'status'                 => $log->status?->value ?? $log->status,
            'checked_in_at'          => $log->checked_in_at,
            'scanned_latitude'       => $log->scanned_latitude,
            'scanned_longitude'      => $log->scanned_longitude,
            'distance_from_property' => $log->distance_from_property,
            'resident'               => $log->user ? [
                'id'         => $log->user->id,
                'name'       => $log->user->name,
                'email'      => $log->user->email,
                'phone'      => $log->user->phone,
                'type'       => $log->user->type?->value ?? $log->user->type,
                'is_blocked' => (bool) $log->user->is_blocked,
            ] : null,
            'bed' => $log->bed ? [
                'id'            => $log->bed->id,
                'occupant_name' => $log->bed->occupant_name,
                'room'          => $log->bed->room ? [
                    'id'          => $log->bed->room->id,
                    'name'        => $log->bed->room->name,
                    'description' => $log->bed->room->description,
                ] : null,
            ] : null,
            'property' => $log->property ? [
                'id'          => $log->property->id,
                'title'       => $log->property->title,
                'city'        => $log->property->city,
                'curfew_time' => $log->property->curfew_time,
            ] : null,
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Resident: تسجيل الحضور بعد مسح QR
    |--------------------------------------------------------------------------
    | POST /v1/attendance/checkin
    */
    public function checkin(CheckinRequest $request)
    {
        $user = $request->user();

        // جلب السكن والسرير
        $property = Property::findOrFail($request->property_id);
        $bed      = Bed::findOrFail($request->bed_id);

        // التحقق إن الطالب هو فعلاً ساكن هذا السرير
        if ($bed->user_id !== $user->id) {
            return response()->json([
                'status'  => false,
                'message' => 'هذا السرير غير مسجَّل باسمك.',
            ], 403);
        }

        $result = $this->attendanceService->checkin(
            $property,
            $bed,
            (float) $request->latitude,
            (float) $request->longitude
        );

        $httpCode = $result['success'] ? 200 : 422;

        return response()->json([
            'status'  => $result['success'],
            'message' => $result['message'],
            'data'    => $result['data'] ?? null,
        ], $httpCode);
    }

    /*
    |--------------------------------------------------------------------------
    | Resident: عرض سجل الحضور الخاص بالطالب
    |--------------------------------------------------------------------------
    | GET /v1/attendance/my?month=2026-08
    */
    public function myLogs(Request $request)
    {
        $user = $request->user();

        $query = AttendanceLog::where('user_id', $user->id)
            ->with([
                'user:id,name,email,phone,type,is_blocked',
                'bed.room:id,property_id,name,description',
                'property:id,title,city,curfew_time'
            ])
            ->orderByDesc('date');

        // فلتر بالشهر (اختياري)
        if ($request->filled('month')) {
            $query->whereYear('date', substr($request->month, 0, 4))
                  ->whereMonth('date', substr($request->month, 5, 2));
        }

        $logs = $query->paginate($request->integer('per_page', 30));

        $summary = $this->buildResidentSummary($user->id, $request->get('month'));

        return response()->json([
            'status'  => true,
            'message' => 'تم استرجاع سجل حضورك بنجاح',
            'summary' => $summary,
            'data'    => $logs->map(fn($log) => $this->formatLog($log)),
            'meta'    => [
                'total'        => $logs->total(),
                'per_page'     => $logs->perPage(),
                'current_page' => $logs->currentPage(),
                'last_page'    => $logs->lastPage(),
            ],
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Property Owner: عرض سجل الحضور اليومي أو الشهري لسكنه
    |--------------------------------------------------------------------------
    | GET /v1/properties/{property}/attendance?date=2026-08-02
    | GET /v1/properties/{property}/attendance?month=2026-08
    */
    public function propertyLogs(Request $request, Property $property)
    {
        // التحقق إن صاحب السكن هو المالك أو أن المستخدم أدمن
        if ($request->user()->id !== $property->user_id && $request->user()->type !== UserTypeEnum::ADMIN) {
            return response()->json([
                'status'  => false,
                'message' => 'غير مصرح لك بعرض سجل هذا السكن.',
            ], 403);
        }

        $query = AttendanceLog::where('property_id', $property->id)
            ->with([
                'user:id,name,email,phone,type,is_blocked',
                'bed.room:id,property_id,name,description',
                'property:id,title,city,curfew_time'
            ])
            ->orderByDesc('date')
            ->orderBy('status');

        // فلتر بتاريخ محدد
        if ($request->filled('date')) {
            $query->whereDate('date', $request->date);
        }
        // فلتر بالشهر
        elseif ($request->filled('month')) {
            $query->whereYear('date', substr($request->month, 0, 4))
                  ->whereMonth('date', substr($request->month, 5, 2));
        }

        // فلتر بالحالة
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $logs = $query->paginate($request->integer('per_page', 30));

        return response()->json([
            'status'  => true,
            'message' => 'تم استرجاع سجل الحضور بنجاح',
            'data'    => $logs->map(fn($log) => $this->formatLog($log)),
            'meta'    => [
                'total'        => $logs->total(),
                'per_page'     => $logs->perPage(),
                'current_page' => $logs->currentPage(),
                'last_page'    => $logs->lastPage(),
            ],
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Property Owner / Admin: سجل الحضور اليومي لسكن محدد
    |--------------------------------------------------------------------------
    | GET /v1/properties/{property}/attendance/daily?date=2026-08-04
    */
    public function dailyLogs(Request $request, Property $property)
    {
        if ($request->user()->id !== $property->user_id && $request->user()->type !== UserTypeEnum::ADMIN) {
            return response()->json([
                'status'  => false,
                'message' => 'غير مصرح لك بعرض سجل هذا السكن.',
            ], 403);
        }

        $date = $request->get('date', Carbon::today()->toDateString());

        $query = AttendanceLog::where('property_id', $property->id)
            ->whereDate('date', $date)
            ->with([
                'user:id,name,email,phone,type,is_blocked',
                'bed.room:id,property_id,name,description',
                'property:id,title,city,curfew_time'
            ])
            ->orderBy('status');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $logs = $query->paginate($request->integer('per_page', 30));

        // حساب ملخص اليوم المحدد
        $statsQuery = AttendanceLog::where('property_id', $property->id)->whereDate('date', $date);
        $total   = $statsQuery->clone()->count();
        $present = $statsQuery->clone()->where('status', AttendanceStatusEnum::PRESENT)->count();
        $late    = $statsQuery->clone()->where('status', AttendanceStatusEnum::LATE)->count();
        $absent  = $statsQuery->clone()->where('status', AttendanceStatusEnum::ABSENT)->count();

        return response()->json([
            'status'  => true,
            'message' => 'تم استرجاع سجل الحضور اليومي بنجاح',
            'date'    => $date,
            'summary' => [
                'total_residents' => $total,
                'present_count'   => $present,
                'late_count'      => $late,
                'absent_count'    => $absent,
            ],
            'data'    => $logs->map(fn($log) => $this->formatLog($log)),
            'meta'    => [
                'total'        => $logs->total(),
                'per_page'     => $logs->perPage(),
                'current_page' => $logs->currentPage(),
                'last_page'    => $logs->lastPage(),
            ],
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Property Owner / Admin: سجل الحضور الشهري لسكن محدد
    |--------------------------------------------------------------------------
    | GET /v1/properties/{property}/attendance/monthly?month=2026-08
    */
    public function monthlyLogs(Request $request, Property $property)
    {
        if ($request->user()->id !== $property->user_id && $request->user()->type !== UserTypeEnum::ADMIN) {
            return response()->json([
                'status'  => false,
                'message' => 'غير مصرح لك بعرض سجل هذا السكن.',
            ], 403);
        }

        $month = $request->get('month', Carbon::now()->format('Y-m'));
        $year  = (int) substr($month, 0, 4);
        $mon   = (int) substr($month, 5, 2);

        $query = AttendanceLog::where('property_id', $property->id)
            ->whereYear('date', $year)
            ->whereMonth('date', $mon)
            ->with([
                'user:id,name,email,phone,type,is_blocked',
                'bed.room:id,property_id,name,description',
                'property:id,title,city,curfew_time'
            ])
            ->orderByDesc('date')
            ->orderBy('status');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        $logs = $query->paginate($request->integer('per_page', 30));

        // إحصائيات الشهر المحدد
        $statsQuery = AttendanceLog::where('property_id', $property->id)
            ->whereYear('date', $year)
            ->whereMonth('date', $mon);
        
        $total   = $statsQuery->clone()->count();
        $present = $statsQuery->clone()->where('status', AttendanceStatusEnum::PRESENT)->count();
        $late    = $statsQuery->clone()->where('status', AttendanceStatusEnum::LATE)->count();
        $absent  = $statsQuery->clone()->where('status', AttendanceStatusEnum::ABSENT)->count();

        return response()->json([
            'status'  => true,
            'message' => 'تم استرجاع سجل الحضور الشهري بنجاح',
            'month'   => $month,
            'summary' => [
                'total_records'   => $total,
                'present_count'   => $present,
                'late_count'      => $late,
                'absent_count'    => $absent,
                'attendance_rate' => $total > 0 ? round((($present + $late) / $total) * 100, 1) : 0,
            ],
            'data'    => $logs->map(fn($log) => $this->formatLog($log)),
            'meta'    => [
                'total'        => $logs->total(),
                'per_page'     => $logs->perPage(),
                'current_page' => $logs->currentPage(),
                'last_page'    => $logs->lastPage(),
            ],
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Property Owner: ملخص إحصائي للحضور
    |--------------------------------------------------------------------------
    | GET /v1/properties/{property}/attendance/summary?month=2026-08
    */
    public function summary(Request $request, Property $property)
    {
        if ($request->user()->id !== $property->user_id && $request->user()->type !== UserTypeEnum::ADMIN) {
            return response()->json([
                'status'  => false,
                'message' => 'غير مصرح لك بعرض ملخص هذا السكن.',
            ], 403);
        }

        $month = $request->get('month', Carbon::now()->format('Y-m'));
        $year  = (int) substr($month, 0, 4);
        $mon   = (int) substr($month, 5, 2);

        $logsQuery = AttendanceLog::where('property_id', $property->id)
            ->whereYear('date', $year)
            ->whereMonth('date', $mon);

        $total   = $logsQuery->clone()->count();
        $present = $logsQuery->clone()->where('status', AttendanceStatusEnum::PRESENT)->count();
        $late    = $logsQuery->clone()->where('status', AttendanceStatusEnum::LATE)->count();
        $absent  = $logsQuery->clone()->where('status', AttendanceStatusEnum::ABSENT)->count();

        // أكثر الطلاب غياباً
        $mostAbsent = AttendanceLog::where('property_id', $property->id)
            ->whereYear('date', $year)
            ->whereMonth('date', $mon)
            ->where('status', AttendanceStatusEnum::ABSENT)
            ->with('user:id,name,email,phone')
            ->selectRaw('user_id, COUNT(*) as absent_count')
            ->groupBy('user_id')
            ->orderByDesc('absent_count')
            ->limit(5)
            ->get()
            ->map(fn($row) => [
                'user'         => $row->user ? [
                    'id'    => $row->user->id,
                    'name'  => $row->user->name,
                    'email' => $row->user->email,
                    'phone' => $row->user->phone,
                ] : null,
                'absent_count' => $row->absent_count,
            ]);

        return response()->json([
            'status'  => true,
            'message' => 'تم استرجاع ملخص الحضور بنجاح',
            'data'    => [
                'month'             => $month,
                'property'          => ['id' => $property->id, 'title' => $property->title],
                'total_records'     => $total,
                'present_count'     => $present,
                'late_count'        => $late,
                'absent_count'      => $absent,
                'attendance_rate'   => $total > 0 ? round((($present + $late) / $total) * 100, 1) : 0,
                'top_absent_residents' => $mostAbsent,
            ],
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Property Owner: تحديد وقت الكيرفيو للسكن
    |--------------------------------------------------------------------------
    | PATCH /v1/properties/{property}/curfew
    */
    public function updateCurfew(UpdateCurfewRequest $request, Property $property)
    {
        if ($request->user()->id !== $property->user_id && $request->user()->type !== UserTypeEnum::ADMIN) {
            return response()->json([
                'status'  => false,
                'message' => 'غير مصرح لك بتعديل هذا السكن.',
            ], 403);
        }

        $property->update(['curfew_time' => $request->curfew_time]);

        return response()->json([
            'status'  => true,
            'message' => 'تم تحديث وقت الكيرفيو بنجاح',
            'data'    => [
                'property_id' => $property->id,
                'curfew_time' => $property->curfew_time,
            ],
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Helper: بناء ملخص الحضور لطالب معين
    |--------------------------------------------------------------------------
    */
    private function buildResidentSummary(int $userId, ?string $month): array
    {
        $query = AttendanceLog::where('user_id', $userId);

        if ($month) {
            $query->whereYear('date', substr($month, 0, 4))
                  ->whereMonth('date', substr($month, 5, 2));
        }

        $total   = $query->clone()->count();
        $present = $query->clone()->where('status', AttendanceStatusEnum::PRESENT)->count();
        $late    = $query->clone()->where('status', AttendanceStatusEnum::LATE)->count();
        $absent  = $query->clone()->where('status', AttendanceStatusEnum::ABSENT)->count();

        return [
            'total'           => $total,
            'present'         => $present,
            'late'            => $late,
            'absent'          => $absent,
            'attendance_rate' => $total > 0 ? round((($present + $late) / $total) * 100, 1) : 0,
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Admin: System-wide Attendance Logs Overview
    |--------------------------------------------------------------------------
    | GET /v1/admin/attendance
    */
    public function adminLogs(Request $request)
    {
        $query = AttendanceLog::with([
            'user:id,name,email,phone,type,is_blocked',
            'bed.room:id,property_id,name,description',
            'property:id,title,city,curfew_time'
        ])->orderByDesc('date');

        if ($request->filled('property_id')) {
            $query->where('property_id', $request->property_id);
        }

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->filled('date')) {
            $query->whereDate('date', $request->date);
        } elseif ($request->filled('month')) {
            $query->whereYear('date', substr($request->month, 0, 4))
                  ->whereMonth('date', substr($request->month, 5, 2));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $logs = $query->paginate($request->integer('per_page', 30));

        return response()->json([
            'status'  => true,
            'message' => 'تم استرجاع سجل الحضور الكلي بنجاح',
            'data'    => $logs->map(fn($log) => $this->formatLog($log)),
            'meta'    => [
                'total'        => $logs->total(),
                'per_page'     => $logs->perPage(),
                'current_page' => $logs->currentPage(),
                'last_page'    => $logs->lastPage(),
            ],
        ]);
    }
}

