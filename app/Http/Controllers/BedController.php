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
            'created_at'    => $bed->created_at,
            'updated_at'    => $bed->updated_at,
        ];
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

        $beds = $room->beds()->orderBy('created_at')->get();

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

        $bed = $room->beds()->create($request->validated());

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
        $room->load('property');

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

        return response()->json([
            'status'  => true,
            'message' => 'تم استرجاع تفاصيل السرير بنجاح',
            'data'    => $this->formatBed($bed),
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Update a bed's occupant name
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

        $bed->update($request->validated());

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
}
