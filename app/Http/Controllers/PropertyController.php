<?php

namespace App\Http\Controllers;

use App\Models\Property;
use App\Http\Requests\Property\StorePropertyRequest;
use App\Http\Requests\Property\UpdatePropertyRequest;
use Illuminate\Http\Request;

class PropertyController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Helper: Format a single property record for API response
    |--------------------------------------------------------------------------
    */
    private function formatProperty(Property $property): array
    {
        return [
            'id'              => $property->id,
            'title'           => $property->title,
            'city'            => $property->city,
            'floor'           => $property->floor,
            'address_details' => $property->address_details,
            'latitude'        => $property->latitude,
            'longitude'       => $property->longitude,
            'radius'          => $property->radius,
            'is_available'    => (bool) $property->is_available,
            'description'     => $property->description,
            'created_at'      => $property->created_at,
            'updated_at'      => $property->updated_at,
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Property Owner: List my own properties
    |--------------------------------------------------------------------------
    | GET /v1/properties/my
    */
    public function myProperties(Request $request)
    {
        $properties = Property::where('user_id', $request->user()->id)
            ->orderByDesc('created_at')
            ->paginate($request->integer('per_page', 15));

        return response()->json([
            'status'  => true,
            'message' => 'تم استرجاع عقاراتك بنجاح',
            'data'    => $properties->map(fn($p) => $this->formatProperty($p)),
            'meta'    => [
                'total'        => $properties->total(),
                'per_page'     => $properties->perPage(),
                'current_page' => $properties->currentPage(),
                'last_page'    => $properties->lastPage(),
            ],
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Property Owner: Add a new property
    |--------------------------------------------------------------------------
    | POST /v1/properties
    */
    public function store(StorePropertyRequest $request)
    {
        $data = $request->validated();
        $data['user_id']      = $request->user()->id;
        $data['is_available'] = $request->boolean('is_available', true);

        $property = Property::create($data);

        return response()->json([
            'status'  => true,
            'message' => 'تم إضافة السكن بنجاح',
            'data'    => $this->formatProperty($property),
        ], 201);
    }

    /*
    |--------------------------------------------------------------------------
    | Property Owner: Show a single property (own only)
    |--------------------------------------------------------------------------
    | GET /v1/properties/{property}
    */
    public function show(Request $request, Property $property)
    {
        // Owners can only view their own; admin can view all (admin uses separate route)
        if ($request->user()->id !== $property->user_id) {
            return response()->json([
                'status'  => false,
                'message' => 'غير مصرح لك بعرض هذا السكن.',
            ], 403);
        }

        return response()->json([
            'status'  => true,
            'message' => 'تم استرجاع تفاصيل السكن بنجاح',
            'data'    => $this->formatProperty($property),
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Property Owner: Get QR Code Data (own only)
    |--------------------------------------------------------------------------
    | GET /v1/properties/{property}/qr-data
    */
    public function qrData(Request $request, Property $property)
    {
        if ($request->user()->id !== $property->user_id) {
            return response()->json([
                'status'  => false,
                'message' => 'غير مصرح لك بعرض هذا السكن.',
            ], 403);
        }

        $property->load('owner');

        return response()->json([
            'status'  => true,
            'message' => 'تم استرجاع بيانات الـ QR كود بنجاح',
            'data'    => [
                'owner_id'        => $property->user_id,
                'owner_name'      => $property->owner?->name,
                'city'            => $property->city,
                'floor'           => $property->floor,
                'address_details' => $property->address_details,
                'latitude'        => $property->latitude,
                'longitude'       => $property->longitude,
            ],
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Property Owner: Update a property (own only)
    |--------------------------------------------------------------------------
    | PUT /v1/properties/{property}
    */
    public function update(UpdatePropertyRequest $request, Property $property)
    {
        // Authorize: only owner can update
        if ($request->user()->id !== $property->user_id) {
            return response()->json([
                'status'  => false,
                'message' => 'غير مصرح لك بتعديل هذا السكن.',
            ], 403);
        }

        $data = $request->validated();

        if ($request->has('is_available')) {
            $data['is_available'] = $request->boolean('is_available');
        }

        $property->update($data);

        return response()->json([
            'status'  => true,
            'message' => 'تم تحديث السكن بنجاح',
            'data'    => $this->formatProperty($property),
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Property Owner: Delete a property (own only)
    |--------------------------------------------------------------------------
    | DELETE /v1/properties/{property}
    */
    public function destroy(Request $request, Property $property)
    {
        // Authorize: only owner can delete
        if ($request->user()->id !== $property->user_id) {
            return response()->json([
                'status'  => false,
                'message' => 'غير مصرح لك بحذف هذا السكن.',
            ], 403);
        }

        $property->delete();

        return response()->json([
            'status'  => true,
            'message' => 'تم حذف السكن بنجاح',
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Admin: List all properties (all owners)
    |--------------------------------------------------------------------------
    | GET /v1/admin/properties
    */
    public function adminIndex(Request $request)
    {
        $query = Property::with('owner');

        // Optional filter by owner
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        // Optional filter by availability
        if ($request->filled('is_available')) {
            $query->where('is_available', filter_var($request->is_available, FILTER_VALIDATE_BOOLEAN));
        }

        $properties = $query->orderByDesc('created_at')
            ->paginate($request->integer('per_page', 15));

        return response()->json([
            'status'  => true,
            'message' => 'تم استرجاع جميع العقارات بنجاح',
            'data'    => $properties->map(fn($p) => array_merge(
                $this->formatProperty($p),
                [
                    'owner' => [
                        'id'    => $p->owner?->id,
                        'name'  => $p->owner?->name,
                        'email' => $p->owner?->email,
                        'phone' => $p->owner?->phone,
                    ],
                ]
            )),
            'meta'    => [
                'total'        => $properties->total(),
                'per_page'     => $properties->perPage(),
                'current_page' => $properties->currentPage(),
                'last_page'    => $properties->lastPage(),
            ],
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Admin: Show a single property (any owner)
    |--------------------------------------------------------------------------
    | GET /v1/admin/properties/{property}
    */
    public function adminShow(Property $property)
    {
        $property->load('owner');

        return response()->json([
            'status'  => true,
            'message' => 'تم استرجاع تفاصيل السكن بنجاح',
            'data'    => array_merge(
                $this->formatProperty($property),
                [
                    'owner' => [
                        'id'    => $property->owner?->id,
                        'name'  => $property->owner?->name,
                        'email' => $property->owner?->email,
                        'phone' => $property->owner?->phone,
                    ],
                ]
            ),
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Admin: Delete any property
    |--------------------------------------------------------------------------
    | DELETE /v1/admin/properties/{property}
    */
    public function adminDestroy(Property $property)
    {
        $property->delete();

        return response()->json([
            'status'  => true,
            'message' => 'تم حذف السكن بنجاح',
        ]);
    }
}
