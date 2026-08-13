<?php

namespace App\Http\Controllers;

use App\Http\Requests\Absence\StoreAbsenceRequest;
use App\Models\Absence;
use App\Models\Bed;
use App\Models\Property;
use Carbon\Carbon;
use Illuminate\Http\Request;

class AbsenceController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Helper: Format a single absence record for API response
    |--------------------------------------------------------------------------
    */
    private function formatAbsence(Absence $absence): array
    {
        return [
            'id'         => $absence->id,
            'start_date' => $absence->start_date?->toDateString(),
            'end_date'   => $absence->end_date?->toDateString(),
            'reason'     => $absence->reason,
            'is_active'  => $this->isActive($absence),
            'created_at' => $absence->created_at,
            'resident'   => $absence->user ? [
                'id'    => $absence->user->id,
                'name'  => $absence->user->name,
                'email' => $absence->user->email,
                'phone' => $absence->user->phone,
            ] : null,
            'property' => $absence->property ? [
                'id'    => $absence->property->id,
                'title' => $absence->property->title,
                'city'  => $absence->property->city,
            ] : null,
            'bed' => $absence->bed ? [
                'id'            => $absence->bed->id,
                'occupant_name' => $absence->bed->occupant_name,
                'room'          => $absence->bed->room ? [
                    'id'   => $absence->bed->room->id,
                    'name' => $absence->bed->room->name,
                ] : null,
            ] : null,
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Helper: Check if the absence is currently active (today is within range)
    |--------------------------------------------------------------------------
    */
    private function isActive(Absence $absence): bool
    {
        $today = Carbon::today();
        return $absence->start_date
            && $absence->end_date
            && $today->between($absence->start_date, $absence->end_date);
    }

    /*
    |--------------------------------------------------------------------------
    | Resident: Report a travel / absence period
    |--------------------------------------------------------------------------
    | POST /v1/resident/absences
    */
    public function store(StoreAbsenceRequest $request)
    {
        $user = $request->user();

        // التحقق من أن الطالب مقيم في سرير حالياً
        $bed = Bed::where('user_id', $user->id)
            ->with('room.property')
            ->first();

        if (! $bed) {
            return response()->json([
                'status'  => false,
                'message' => 'أنت غير مسجل في أي سكن حالياً لتقديم بلاغ غياب.',
            ], 422);
        }

        $property = $bed->room?->property;

        if (! $property) {
            return response()->json([
                'status'  => false,
                'message' => 'لا يمكن تحديد السكن المرتبط بسريرك. يرجى التواصل مع إدارة السكن.',
            ], 422);
        }

        $absence = Absence::create([
            'user_id'     => $user->id,
            'property_id' => $property->id,
            'bed_id'      => $bed->id,
            'start_date'  => $request->start_date,
            'end_date'    => $request->end_date,
            'reason'      => $request->reason,
        ]);

        $absence->load(['user', 'property', 'bed.room']);

        return response()->json([
            'status'  => true,
            'message' => 'تم تسجيل بلاغ الغياب/السفر بنجاح.',
            'data'    => $this->formatAbsence($absence),
        ], 201);
    }

    /*
    |--------------------------------------------------------------------------
    | Resident: List my own absence reports
    |--------------------------------------------------------------------------
    | GET /v1/resident/absences
    |
    | Query Params (optional):
    |   - active=1  : فلترة على البلاغات النشطة فقط (اليوم داخل الفترة)
    */
    public function myAbsences(Request $request)
    {
        $user = $request->user();

        $query = Absence::where('user_id', $user->id)
            ->with(['property', 'bed.room'])
            ->orderByDesc('start_date');

        // فلتر البلاغات النشطة حالياً
        if ($request->boolean('active')) {
            $today = Carbon::today()->toDateString();
            $query->where('start_date', '<=', $today)
                  ->where('end_date', '>=', $today);
        }

        $absences = $query->paginate($request->integer('per_page', 15));

        return response()->json([
            'status'  => true,
            'message' => 'تم استرجاع بلاغات الغياب بنجاح.',
            'data'    => $absences->map(fn($a) => $this->formatAbsence($a)),
            'meta'    => [
                'total'        => $absences->total(),
                'per_page'     => $absences->perPage(),
                'current_page' => $absences->currentPage(),
                'last_page'    => $absences->lastPage(),
            ],
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Property Owner: View all absences for a specific property
    |--------------------------------------------------------------------------
    | GET /v1/properties/{property}/absences
    |
    | Query Params (optional):
    |   - active=1      : البلاغات النشطة حالياً فقط (اليوم داخل الفترة)
    |   - per_page=N    : عدد النتائج في الصفحة (الافتراضي: 15)
    */
    public function ownerAbsences(Request $request, Property $property)
    {
        $user = $request->user();

        // التحقق من صلاحية الوصول للسكن
        if ($user->id !== $property->user_id && $user->type !== \App\Enums\UserTypeEnum::ADMIN) {
            return response()->json([
                'status'  => false,
                'message' => 'غير مصرح لك بعرض بلاغات هذا السكن.',
            ], 403);
        }

        $query = Absence::where('property_id', $property->id)
            ->with(['user', 'property', 'bed.room'])
            ->orderByDesc('start_date');

        // فلتر البلاغات النشطة حالياً
        if ($request->boolean('active')) {
            $today = Carbon::today()->toDateString();
            $query->where('start_date', '<=', $today)
                  ->where('end_date', '>=', $today);
        }

        $absences = $query->paginate($request->integer('per_page', 15));

        return response()->json([
            'status'  => true,
            'message' => 'تم استرجاع بلاغات الغياب للسكن بنجاح.',
            'data'    => $absences->map(fn($a) => $this->formatAbsence($a)),
            'meta'    => [
                'total'        => $absences->total(),
                'per_page'     => $absences->perPage(),
                'current_page' => $absences->currentPage(),
                'last_page'    => $absences->lastPage(),
            ],
        ]);
    }
}
