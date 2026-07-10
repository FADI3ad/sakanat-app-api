<?php

namespace App\Http\Controllers;

use App\Models\Service;
use App\Models\Provider;
use App\Http\Requests\Service\StoreServiceRequest;
use App\Http\Requests\Service\UpdateServiceRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ServiceController extends Controller
{
    /**
     * Display a listing of the services.
     */
    public function index(Request $request)
    {
        $services = Service::with(['provider.user', 'area', 'type'])
            ->paginate($request->integer('per_page', 15));

        return response()->json([
            'status'  => true,
            'message' => 'تم استرجاع الخدمات بنجاح',
            'data'    => $services->map(fn($service) => [
                'id'                 => $service->id,
                'title'              => $service->title,
                'description'        => $service->description,
                'image'              => $service->image ? asset('storage/' . $service->image) : null,
                'is_available'       => (bool) $service->is_available,
                'delivery_available' => (bool) $service->delevery_available,
                'price'              => $service->price,
                'area'               => $service->area?->name,
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

    /**
     * Store a newly created service in storage.
     */
    public function store(StoreServiceRequest $request)
    {
        $user = $request->user();
        $provider = $user->provider ?: Provider::create(['user_id' => $user->id]);

        // Check: provider cannot have two services of the same type
        $alreadyExists = Service::where('provider_id', $provider->id)
            ->where('type_id', $request->type_id)
            ->exists();

        if ($alreadyExists) {
            return response()->json([
                'status'  => false,
                'message' => 'لديك خدمة مسجلة بهذا النوع بالفعل، لا يمكن إضافة أكثر من خدمة بنفس النوع.',
            ], 409);
        }

        $data = $request->validated();

        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('services', 'public');
        }

        $data['delevery_available'] = $request->boolean('delevery_available');
        $data['is_available']       = $request->boolean('is_available', true);
        $data['provider_id']        = $provider->id;

        $service = Service::create($data);

        $service->load(['provider.user', 'area', 'type']);

        return response()->json([
            'status'  => true,
            'message' => 'تم إضافة الخدمة بنجاح',
            'data'    => [
                'id'                 => $service->id,
                'title'              => $service->title,
                'description'        => $service->description,
                'image'              => $service->image ? asset('storage/' . $service->image) : null,
                'is_available'       => (bool) $service->is_available,
                'delivery_available' => (bool) $service->delevery_available,
                'price'              => $service->price,
                'area'               => $service->area?->name,
                'type'               => $service->type?->name,
                'provider'           => [
                    'id'    => $service->provider?->id,
                    'name'  => $service->provider?->user?->name,
                    'phone' => $service->provider?->user?->phone,
                ],
            ]
        ], 201);
    }

    /**
     * Display the specified service.
     */
    public function show(Service $service)
    {
        $service->load([
            'provider.user',
            'area',
            'type',
            'comments' => fn($q) => $q->where('is_active', true)
                                      ->with('user')
                                      ->latest()
                                      ->limit(10),
        ]);

        return response()->json([
            'status'  => true,
            'message' => 'تم استرجاع تفاصيل الخدمة بنجاح',
            'data'    => [
                'id'                 => $service->id,
                'title'              => $service->title,
                'description'        => $service->description,
                'image'              => $service->image ? asset('storage/' . $service->image) : null,
                'is_available'       => (bool) $service->is_available,
                'delivery_available' => (bool) $service->delevery_available,
                'price'              => $service->price,
                'area'               => $service->area?->name,
                'type'               => $service->type?->name,
                'provider'           => [
                    'id'    => $service->provider?->id,
                    'name'  => $service->provider?->user?->name,
                    'phone' => $service->provider?->user?->phone,
                ],
                'comments'           => $service->comments->map(fn($comment) => [
                    'id'         => $comment->id,
                    'body'       => $comment->body,
                    'created_at' => $comment->created_at,
                    'user'       => [
                        'id'   => $comment->user?->id,
                        'name' => $comment->user?->name,
                    ],
                ]),
            ],
        ]);
    }

    /**
     * Update the specified service in storage.
     */
    public function update(UpdateServiceRequest $request, Service $service)
    {
        $user = $request->user();
        $provider = $user->provider;

        // Authorize owner
        if (!$provider || $service->provider_id !== $provider->id) {
            return response()->json([
                'status'  => false,
                'message' => 'غير مصرح لك بتعديل هذه الخدمة.',
            ], 403);
        }

        $data = $request->validated();

        if ($request->hasFile('image')) {
            // Delete old image if exists
            if ($service->image) {
                Storage::disk('public')->delete($service->image);
            }
            $data['image'] = $request->file('image')->store('services', 'public');
        }

        if ($request->has('delevery_available')) {
            $data['delevery_available'] = $request->boolean('delevery_available');
        }
        if ($request->has('is_available')) {
            $data['is_available'] = $request->boolean('is_available');
        }

        $service->update($data);

        $service->load(['provider.user', 'area', 'type']);

        return response()->json([
            'status'  => true,
            'message' => 'تم تحديث الخدمة بنجاح',
            'data'    => [
                'id'                 => $service->id,
                'title'              => $service->title,
                'description'        => $service->description,
                'image'              => $service->image ? asset('storage/' . $service->image) : null,
                'is_available'       => (bool) $service->is_available,
                'delivery_available' => (bool) $service->delevery_available,
                'price'              => $service->price,
                'area'               => $service->area?->name,
                'type'               => $service->type?->name,
                'provider'           => [
                    'id'    => $service->provider?->id,
                    'name'  => $service->provider?->user?->name,
                    'phone' => $service->provider?->user?->phone,
                ],
            ],
        ]);
    }

    /**
     * Remove the specified service from storage.
     */
    public function destroy(Request $request, Service $service)
    {
        $user = $request->user();
        $provider = $user->provider;

        // Authorize owner
        if (!$provider || $service->provider_id !== $provider->id) {
            return response()->json([
                'status'  => false,
                'message' => 'غير مصرح لك بحذف هذه الخدمة.',
            ], 403);
        }

        if ($service->image) {
            Storage::disk('public')->delete($service->image);
        }

        $service->delete();

        return response()->json([
            'status'  => true,
            'message' => 'تم حذف الخدمة بنجاح',
        ]);
    }
}
