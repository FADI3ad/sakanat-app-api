<?php

namespace App\Http\Controllers;

use App\Models\DeliveryService;
use App\Models\Type;
use App\Http\Requests\DeliveryService\StoreDeliveryServiceRequest;
use App\Http\Requests\DeliveryService\UpdateDeliveryServiceRequest;
use Illuminate\Http\Request;

class DeliveryServiceController extends Controller
{
    /**
     * Display a listing of the delivery services.
     */
    public function index(Request $request)
    {
        $query = DeliveryService::with('type:id,name');

        if ($request->has('type_id')) {
            $query->where('type_id', $request->input('type_id'));
        }

        if ($request->has('is_available')) {
            $query->where('is_available', filter_var($request->input('is_available'), FILTER_VALIDATE_BOOLEAN));
        }

        $deliveryServices = $query->latest()->paginate($request->integer('per_page', 15));

        return response()->json([
            'status'  => true,
            'message' => 'تم استرجاع خدمات التوصيل بنجاح',
            'data'    => $deliveryServices->map(fn($item) => [
                'id'           => $item->id,
                'name'         => $item->name,
                'phone'        => $item->phone,
                'vehicle_type' => $item->vehicle_type,
                'is_available' => (bool) $item->is_available,
                'type'         => [
                    'id'   => $item->type?->id,
                    'name' => $item->type?->name,
                ],
                'created_at'   => $item->created_at?->toIso8601String(),
            ]),
            'meta' => [
                'total'        => $deliveryServices->total(),
                'per_page'     => $deliveryServices->perPage(),
                'current_page' => $deliveryServices->currentPage(),
                'last_page'    => $deliveryServices->lastPage(),
            ],
        ]);
    }

    /**
     * Store a newly created delivery service in storage.
     */
    public function store(StoreDeliveryServiceRequest $request)
    {
        $deliveryService = DeliveryService::create($request->validated());
        $deliveryService->load('type:id,name');

        return response()->json([
            'status'  => true,
            'message' => 'تم إضافة خدمة التوصيل بنجاح',
            'data'    => [
                'id'           => $deliveryService->id,
                'name'         => $deliveryService->name,
                'phone'        => $deliveryService->phone,
                'vehicle_type' => $deliveryService->vehicle_type,
                'is_available' => (bool) $deliveryService->is_available,
                'type'         => [
                    'id'   => $deliveryService->type?->id,
                    'name' => $deliveryService->type?->name,
                ],
                'created_at'   => $deliveryService->created_at?->toIso8601String(),
            ]
        ], 201);
    }

    /**
     * Display the specified delivery service.
     */
    public function show(DeliveryService $deliveryService)
    {
        $deliveryService->load('type:id,name');

        return response()->json([
            'status'  => true,
            'message' => 'تم استرجاع تفاصيل خدمة التوصيل بنجاح',
            'data'    => [
                'id'           => $deliveryService->id,
                'name'         => $deliveryService->name,
                'phone'        => $deliveryService->phone,
                'vehicle_type' => $deliveryService->vehicle_type,
                'is_available' => (bool) $deliveryService->is_available,
                'type'         => [
                    'id'   => $deliveryService->type?->id,
                    'name' => $deliveryService->type?->name,
                ],
                'created_at'   => $deliveryService->created_at?->toIso8601String(),
            ],
        ]);
    }

    /**
     * Update the specified delivery service in storage.
     */
    public function update(UpdateDeliveryServiceRequest $request, DeliveryService $deliveryService)
    {
        $deliveryService->update($request->validated());
        $deliveryService->load('type:id,name');

        return response()->json([
            'status'  => true,
            'message' => 'تم تحديث خدمة التوصيل بنجاح',
            'data'    => [
                'id'           => $deliveryService->id,
                'name'         => $deliveryService->name,
                'phone'        => $deliveryService->phone,
                'vehicle_type' => $deliveryService->vehicle_type,
                'is_available' => (bool) $deliveryService->is_available,
                'type'         => [
                    'id'   => $deliveryService->type?->id,
                    'name' => $deliveryService->type?->name,
                ],
                'created_at'   => $deliveryService->created_at?->toIso8601String(),
            ],
        ]);
    }

    /**
     * Remove the specified delivery service from storage.
     */
    public function destroy(DeliveryService $deliveryService)
    {
        $deliveryService->delete();

        return response()->json([
            'status'  => true,
            'message' => 'تم حذف خدمة التوصيل بنجاح',
        ]);
    }

    /**
     * Display a listing of delivery services belonging to a specific type.
     */
    public function byType(Type $type, Request $request)
    {
        $query = $type->deliveryServices();

        if ($request->has('is_available')) {
            $query->where('is_available', filter_var($request->input('is_available'), FILTER_VALIDATE_BOOLEAN));
        }

        $deliveryServices = $query->latest()->paginate($request->integer('per_page', 15));

        return response()->json([
            'status'  => true,
            'message' => 'تم استرجاع خدمات التوصيل لهذا التصنيف بنجاح',
            'type'    => [
                'id'   => $type->id,
                'name' => $type->name,
            ],
            'data'    => $deliveryServices->map(fn($item) => [
                'id'           => $item->id,
                'name'         => $item->name,
                'phone'        => $item->phone,
                'vehicle_type' => $item->vehicle_type,
                'is_available' => (bool) $item->is_available,
                'created_at'   => $item->created_at?->toIso8601String(),
            ]),
            'meta' => [
                'total'        => $deliveryServices->total(),
                'per_page'     => $deliveryServices->perPage(),
                'current_page' => $deliveryServices->currentPage(),
                'last_page'    => $deliveryServices->lastPage(),
            ],
        ]);
    }
}
