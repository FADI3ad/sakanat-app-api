<?php

namespace App\Http\Controllers;

use App\Models\Area;
use App\Http\Requests\Area\StoreAreaRequest;
use App\Http\Requests\Area\UpdateAreaRequest;
use Illuminate\Http\Request;

class AreaController extends Controller
{
    /**
     * Display a listing of the areas.
     */
    public function index(Request $request)
    {
        $areas = Area::orderBy('name')->paginate($request->integer('per_page', 15));

        return response()->json([
            'status'  => true,
            'message' => 'تم استرجاع المناطق بنجاح',
            'data'    => $areas->map(fn($area) => [
                'id'   => $area->id,
                'name' => $area->name,
            ]),
            'meta' => [
                'total'        => $areas->total(),
                'per_page'     => $areas->perPage(),
                'current_page' => $areas->currentPage(),
                'last_page'    => $areas->lastPage(),
            ],
        ]);
    }

    /**
     * Store a newly created area in storage.
     */
    public function store(StoreAreaRequest $request)
    {
        $area = Area::create($request->validated());

        return response()->json([
            'status'  => true,
            'message' => 'تم إضافة المنطقة بنجاح',
            'data'    => [
                'id'   => $area->id,
                'name' => $area->name,
            ],
        ], 201);
    }

    /**
     * Display the specified area.
     */
    public function show(Area $area)
    {
        return response()->json([
            'status'  => true,
            'message' => 'تم استرجاع تفاصيل المنطقة بنجاح',
            'data'    => [
                'id'   => $area->id,
                'name' => $area->name,
            ],
        ]);
    }

    /**
     * Update the specified area in storage.
     */
    public function update(UpdateAreaRequest $request, Area $area)
    {
        $area->update($request->validated());

        return response()->json([
            'status'  => true,
            'message' => 'تم تحديث المنطقة بنجاح',
            'data'    => [
                'id'   => $area->id,
                'name' => $area->name,
            ],
        ]);
    }

    /**
     * Remove the specified area from storage.
     */
    public function destroy(Area $area)
    {
        $area->delete();

        return response()->json([
            'status'  => true,
            'message' => 'تم حذف المنطقة بنجاح',
        ]);
    }

    /**
     * Display a listing of services belonging to the specified area.
     */
    public function services(Area $area, Request $request)
    {
        $services = $area->services()
            ->with(['provider.user', 'type'])
            ->paginate($request->integer('per_page', 15));

        return response()->json([
            'status'  => true,
            'message' => 'تم استرجاع خدمات المنطقة بنجاح',
            'area'    => [
                'id'   => $area->id,
                'name' => $area->name,
            ],
            'data'    => $services->map(fn($service) => [
                'id'                 => $service->id,
                'title'              => $service->title,
                'description'        => $service->description,
                'image'              => $service->image ? asset('storage/' . $service->image) : null,
                'is_available'       => (bool) $service->is_available,
                'delivery_available' => (bool) $service->delevery_available,
                'price'              => $service->price,
                'type'               => $service->type?->name,
                'provider'           => [
                    'id'    => $service->provider?->id,
                    'name'  => $service->provider?->user?->name,
                    'phone' => $service->provider?->user?->phone,
                ],
            ]),
            'meta' => [
                'total'        => $services->total(),
                'per_page'     => $services->perPage(),
                'current_page' => $services->currentPage(),
                'last_page'    => $services->lastPage(),
            ],
        ]);
    }
}
