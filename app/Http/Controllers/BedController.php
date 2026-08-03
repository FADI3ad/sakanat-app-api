<?php

namespace App\Http\Controllers;

use App\Models\Room;
use App\Models\Bed;
use App\Http\Requests\Bed\StoreBedRequest;
use App\Http\Requests\Bed\UpdateBedRequest;
use Illuminate\Http\Request;

class BedController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Helper: Format a single bed record for API response
    |--------------------------------------------------------------------------
    */
    private function formatBed(Bed $bed): array
    {
        return [
            'id'            => $bed->id,
            'room_id'       => $bed->room_id,
            'occupant_name' => $bed->occupant_name,
            'user_id'       => $bed->user_id,
            'resident'      => $bed->resident ? [
                'id'    => $bed->resident->id,
                'name'  => $bed->resident->name,
                'email' => $bed->resident->email,
                'phone' => $bed->resident->phone,
            ] : null,
            'created_at'    => $bed->created_at,
            'updated_at'    => $bed->updated_at,
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Helper: Resolve resident user by phone if provided in request
    |--------------------------------------------------------------------------
    */
    private function resolveResidentData(array $data): array|\Illuminate\Http\JsonResponse
    {
        $phone = $data['student_phone'] ?? $data['phone'] ?? null;

        if ($phone) {
            $student = \App\Models\User::where('phone', $phone)
                ->where('type', \App\Enums\UserTypeEnum::RESIDENT)
                ->first();

            if (! $student) {
                return response()->json([
                    'status'  => false,
                    'message' => 'لم يتم العثور على طالب مقيم بهاتف: ' . $phone,
                ], 422);
            }

            $data['user_id'] = $student->id;
            if (empty($data['occupant_name'])) {
                $data['occupant_name'] = $student->name;
            }
        }

        unset($data['student_phone'], $data['phone']);
        return $data;
    }

    /*
    |--------------------------------------------------------------------------
    | Helper: Ensure the authenticated user owns the room's property
    |--------------------------------------------------------------------------
    */
    private function authorizeRoomOwner(Request $request, Room $room): bool
    {
        return $request->user()->id === $room->property->user_id;
    }

    /*
    |--------------------------------------------------------------------------
    | List all beds in a room
    |--------------------------------------------------------------------------
    | GET /v1/rooms/{room}/beds
    */
    public function index(Request $request, Room $room)
    {
        $room->load('property');

        if (! $this->authorizeRoomOwner($request, $room)) {
            return response()->json([
                'status'  => false,
                'message' => 'غير مصرح لك بعرض أسرّة هذه الغرفة.',
            ], 403);
        }

        $beds = $room->beds()->with('resident')->orderBy('created_at')->get();

        return response()->json([
            'status'  => true,
            'message' => 'تم استرجاع الأسرّة بنجاح',
            'data'    => $beds->map(fn($bed) => $this->formatBed($bed)),
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Add a new bed to a room
    |--------------------------------------------------------------------------
    | POST /v1/rooms/{room}/beds
    */
    public function store(StoreBedRequest $request, Room $room)
    {
        $room->load('property');

        if (! $this->authorizeRoomOwner($request, $room)) {
            return response()->json([
                'status'  => false,
                'message' => 'غير مصرح لك بإضافة سرير لهذه الغرفة.',
            ], 403);
        }

        $data = $this->resolveResidentData($request->validated());

        if ($data instanceof \Illuminate\Http\JsonResponse) {
            return $data;
        }

        $bed = $room->beds()->create($data);
        $bed->load('resident');

        return response()->json([
            'status'  => true,
            'message' => 'تم إضافة السرير بنجاح',
            'data'    => $this->formatBed($bed),
        ], 201);
    }

    /*
    |--------------------------------------------------------------------------
    | Show a single bed
    |--------------------------------------------------------------------------
    | GET /v1/rooms/{room}/beds/{bed}
    */
    public function show(Request $request, Room $room, Bed $bed)
    {
        $room->load(['property', 'beds.resident']);

        if (! $this->authorizeRoomOwner($request, $room)) {
            return response()->json([
                'status'  => false,
                'message' => 'غير مصرح لك بعرض هذا السرير.',
            ], 403);
        }

        if ($bed->room_id !== $room->id) {
            return response()->json([
                'status'  => false,
                'message' => 'هذا السرير لا ينتمي لهذه الغرفة.',
            ], 404);
        }

        $bed->load('resident');

        return response()->json([
            'status'  => true,
            'message' => 'تم استرجاع تفاصيل السرير بنجاح',
            'data'    => $this->formatBed($bed),
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Update a bed's occupant name / resident
    |--------------------------------------------------------------------------
    | PUT /v1/rooms/{room}/beds/{bed}
    */
    public function update(UpdateBedRequest $request, Room $room, Bed $bed)
    {
        $room->load('property');

        if (! $this->authorizeRoomOwner($request, $room)) {
            return response()->json([
                'status'  => false,
                'message' => 'غير مصرح لك بتعديل هذا السرير.',
            ], 403);
        }

        if ($bed->room_id !== $room->id) {
            return response()->json([
                'status'  => false,
                'message' => 'هذا السرير لا ينتمي لهذه الغرفة.',
            ], 404);
        }

        $data = $this->resolveResidentData($request->validated());

        if ($data instanceof \Illuminate\Http\JsonResponse) {
            return $data;
        }

        $bed->update($data);
        $bed->load('resident');

        return response()->json([
            'status'  => true,
            'message' => 'تم تحديث بيانات السرير بنجاح',
            'data'    => $this->formatBed($bed),
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Delete a bed
    |--------------------------------------------------------------------------
    | DELETE /v1/rooms/{room}/beds/{bed}
    */
    public function destroy(Request $request, Room $room, Bed $bed)
    {
        $room->load('property');

        if (! $this->authorizeRoomOwner($request, $room)) {
            return response()->json([
                'status'  => false,
                'message' => 'غير مصرح لك بحذف هذا السرير.',
            ], 403);
        }

        if ($bed->room_id !== $room->id) {
            return response()->json([
                'status'  => false,
                'message' => 'هذا السرير لا ينتمي لهذه الغرفة.',
            ], 404);
        }

        $bed->delete();

        return response()->json([
            'status'  => true,
            'message' => 'تم حذف السرير بنجاح',
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Resident: Get my assigned bed, room, and property details
    |--------------------------------------------------------------------------
    | GET /v1/resident/my-residence
    */
    public function myResidence(Request $request)
    {
        $user = $request->user();

        $bed = Bed::where('user_id', $user->id)
            ->with(['room.property.owner'])
            ->first();

        if (! $bed) {
            return response()->json([
                'status'  => false,
                'message' => 'أنت غير مسجَّل في أي سرير حالياً.',
                'data'    => null,
            ], 404);
        }

        $room     = $bed->room;
        $property = $room?->property;
        $owner    = $property?->owner;

        return response()->json([
            'status'  => true,
            'message' => 'تم استرجاع تفاصيل سكنك بنجاح',
            'data'    => [
                'bed' => [
                    'id'            => $bed->id,
                    'occupant_name' => $bed->occupant_name,
                    'created_at'    => $bed->created_at,
                    'updated_at'    => $bed->updated_at,
                ],
                'room' => $room ? [
                    'id'          => $room->id,
                    'name'        => $room->name,
                    'description' => $room->description,
                ] : null,
                'property' => $property ? [
                    'id'              => $property->id,
                    'title'           => $property->title,
                    'city'            => $property->city,
                    'floor'           => $property->floor,
                    'address_details' => $property->address_details,
                    'latitude'        => $property->latitude,
                    'longitude'       => $property->longitude,
                    'radius'          => $property->radius,
                    'curfew_time'     => $property->curfew_time,
                    'is_available'    => (bool) $property->is_available,
                    'description'     => $property->description,
                    'owner'           => $owner ? [
                        'id'    => $owner->id,
                        'name'  => $owner->name,
                        'email' => $owner->email,
                        'phone' => $owner->phone,
                    ] : null,
                ] : null,
            ],
        ]);
    }
}
