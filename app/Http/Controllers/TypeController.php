<?php

namespace App\Http\Controllers;

use App\Models\Type;
use App\Http\Requests\Type\StoreTypeRequest;
use App\Http\Requests\Type\UpdateTypeRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class TypeController extends Controller
{
    /**
     * Display a listing of the types.
     */
    public function index(Request $request)
    {
        $types = Type::orderBy('sort_order')->paginate($request->integer('per_page', 15));

        return response()->json([
            'status'  => true,
            'message' => 'تم استرجاع أنواع الخدمات بنجاح',
            'data'    => $types->map(fn($type) => [
                'id'          => $type->id,
                'name'        => $type->name,
                'description' => $type->description,
                'sort_order'  => $type->sort_order,
                'status'      => (bool) $type->status,
                'icon'        => $type->icon ? asset('storage/' . $type->icon) : null,
            ]),
            'meta' => [
                'total'        => $types->total(),
                'per_page'     => $types->perPage(),
                'current_page' => $types->currentPage(),
                'last_page'    => $types->lastPage(),
            ],
        ]);
    }

    /**
     * Store a newly created type in storage.
     */
    public function store(StoreTypeRequest $request)
    {
        $data = $request->validated();

        if ($request->hasFile('icon')) {
            $data['icon'] = $request->file('icon')->store('types', 'public');
        }

        $type = Type::create($data);

        return response()->json([
            'status'  => true,
            'message' => 'تم إضافة نوع الخدمة بنجاح',
            'data'    => [
                'id'          => $type->id,
                'name'        => $type->name,
                'description' => $type->description,
                'sort_order'  => $type->sort_order,
                'status'      => (bool) $type->status,
                'icon'        => $type->icon ? asset('storage/' . $type->icon) : null,
            ]
        ], 201);
    }

    /**
     * Display the specified type.
     */
    public function show(Type $type)
    {
        return response()->json([
            'status'  => true,
            'message' => 'تم استرجاع تفاصيل نوع الخدمة بنجاح',
            'data'    => [
                'id'          => $type->id,
                'name'        => $type->name,
                'description' => $type->description,
                'sort_order'  => $type->sort_order,
                'status'      => (bool) $type->status,
                'icon'        => $type->icon ? asset('storage/' . $type->icon) : null,
            ],
        ]);
    }

    /**
     * Update the specified type in storage.
     */
    public function update(UpdateTypeRequest $request, Type $type)
    {
        $data = $request->validated();

        if ($request->hasFile('icon')) {
            // Delete old icon if exists
            if ($type->icon) {
                Storage::disk('public')->delete($type->icon);
            }
            $data['icon'] = $request->file('icon')->store('types', 'public');
        }

        $type->update($data);

        return response()->json([
            'status'  => true,
            'message' => 'تم تحديث نوع الخدمة بنجاح',
            'data'    => [
                'id'          => $type->id,
                'name'        => $type->name,
                'description' => $type->description,
                'sort_order'  => $type->sort_order,
                'status'      => (bool) $type->status,
                'icon'        => $type->icon ? asset('storage/' . $type->icon) : null,
            ],
        ]);
    }

    /**
     * Remove the specified type from storage.
     */
    public function destroy(Type $type)
    {
        if ($type->icon) {
            Storage::disk('public')->delete($type->icon);
        }

        $type->delete();

        return response()->json([
            'status'  => true,
            'message' => 'تم حذف نوع الخدمة بنجاح',
        ]);
    }

    /**
     * Display a listing of services belonging to the specified type.
     */
    public function services(Type $type, Request $request)
    {
        $services = $type->services()->with(['provider.user', 'area'])->paginate($request->integer('per_page', 15));

        return response()->json([
            'status'  => true,
            'message' => 'تم استرجاع خدمات التصنيف بنجاح',
            'type'    => [
                'id'   => $type->id,
                'name' => $type->name,
            ],
            'data'    => $services->map(fn($service) => [
                'id'                 => $service->id,
                'title'              => $service->title,
                'description'        => $service->description,
                'image'              => $service->image ? asset('storage/' . $service->image) : null,
                'is_available'       => (bool) $service->is_available,
                'delivery_available' => (bool) $service->delevery_available,
                'price'              => $service->price,
                'area'               => $service->area?->name,
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
