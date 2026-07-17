<?php

namespace App\Http\Controllers;

use App\Models\Property;
use App\Models\Room;
use App\Http\Requests\Room\StoreRoomRequest;
use App\Http\Requests\Room\UpdateRoomRequest;
use Illuminate\Http\Request;

class RoomController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Helper: Format a single room record for API response
    |--------------------------------------------------------------------------
    */
    private function formatRoom(Room $room, bool $withBeds = false): array
    {
        $data = [
            'id'          => $room->id,
            'property_id' => $room->property_id,
            'name'        => $room->name,
            'description' => $room->description,
            'beds_count'  => $room->beds()->count(),
            'created_at'  => $room->created_at,
            'updated_at'  => $room->updated_at,
        ];

        if ($withBeds) {
            $data['beds'] = $room->beds->map(fn($bed) => [
                'id'            => $bed->id,
                'occupant_name' => $bed->occupant_name,
                'created_at'    => $bed->created_at,
                'updated_at'    => $bed->updated_at,
            ])->values();
        }

        return $data;
    }

    /*
    |--------------------------------------------------------------------------
    | Helper: Ensure the authenticated user owns the property
    |--------------------------------------------------------------------------
    */
    private function authorizePropertyOwner(Request $request, Property $property): bool
    {
        return $request->user()->id === $property->user_id;
    }

    /*
    |--------------------------------------------------------------------------
    | List all rooms in a property
    |--------------------------------------------------------------------------
    | GET /v1/properties/{property}/rooms
    */
    public function index(Request $request, Property $property)
    {
        if (! $this->authorizePropertyOwner($request, $property)) {
            return response()->json([
                'status'  => false,
                'message' => 'غير مصرح لك بعرض غرف هذا السكن.',
            ], 403);
        }

        $rooms = $property->rooms()->withCount('beds')->orderBy('created_at')->get();

        return response()->json([
            'status'  => true,
            'message' => 'تم استرجاع الغرف بنجاح',
            'data'    => $rooms->map(fn($room) => $this->formatRoom($room, true)),
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Add a new room to a property
    |--------------------------------------------------------------------------
    | POST /v1/properties/{property}/rooms
    */
    public function store(StoreRoomRequest $request, Property $property)
    {
        if (! $this->authorizePropertyOwner($request, $property)) {
            return response()->json([
                'status'  => false,
                'message' => 'غير مصرح لك بإضافة غرف لهذا السكن.',
            ], 403);
        }

        $room = $property->rooms()->create($request->validated());

        return response()->json([
            'status'  => true,
            'message' => 'تم إضافة الغرفة بنجاح',
            'data'    => $this->formatRoom($room),
        ], 201);
    }

    /*
    |--------------------------------------------------------------------------
    | Show a single room with its beds
    |--------------------------------------------------------------------------
    | GET /v1/properties/{property}/rooms/{room}
    */
    public function show(Request $request, Property $property, Room $room)
    {
        if (! $this->authorizePropertyOwner($request, $property)) {
            return response()->json([
                'status'  => false,
                'message' => 'غير مصرح لك بعرض هذه الغرفة.',
            ], 403);
        }

        // تأكد إن الغرفة فعلاً تابعة للعقار ده
        if ($room->property_id !== $property->id) {
            return response()->json([
                'status'  => false,
                'message' => 'هذه الغرفة لا تنتمي لهذا السكن.',
            ], 404);
        }

        $room->load('beds');

        return response()->json([
            'status'  => true,
            'message' => 'تم استرجاع تفاصيل الغرفة بنجاح',
            'data'    => $this->formatRoom($room, true),
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Update a room's details
    |--------------------------------------------------------------------------
    | PUT /v1/properties/{property}/rooms/{room}
    */
    public function update(UpdateRoomRequest $request, Property $property, Room $room)
    {
        if (! $this->authorizePropertyOwner($request, $property)) {
            return response()->json([
                'status'  => false,
                'message' => 'غير مصرح لك بتعديل هذه الغرفة.',
            ], 403);
        }

        if ($room->property_id !== $property->id) {
            return response()->json([
                'status'  => false,
                'message' => 'هذه الغرفة لا تنتمي لهذا السكن.',
            ], 404);
        }

        $room->update($request->validated());

        return response()->json([
            'status'  => true,
            'message' => 'تم تحديث الغرفة بنجاح',
            'data'    => $this->formatRoom($room),
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Delete a room (and all its beds automatically via cascade)
    |--------------------------------------------------------------------------
    | DELETE /v1/properties/{property}/rooms/{room}
    */
    public function destroy(Request $request, Property $property, Room $room)
    {
        if (! $this->authorizePropertyOwner($request, $property)) {
            return response()->json([
                'status'  => false,
                'message' => 'غير مصرح لك بحذف هذه الغرفة.',
            ], 403);
        }

        if ($room->property_id !== $property->id) {
            return response()->json([
                'status'  => false,
                'message' => 'هذه الغرفة لا تنتمي لهذا السكن.',
            ], 404);
        }

        $room->delete();

        return response()->json([
            'status'  => true,
            'message' => 'تم حذف الغرفة وجميع أسرّتها بنجاح',
        ]);
    }
}
