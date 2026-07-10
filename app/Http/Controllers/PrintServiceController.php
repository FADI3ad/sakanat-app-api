<?php

namespace App\Http\Controllers;

use App\Models\PrintService;
use App\Models\Service;
use App\Http\Requests\PrintService\StorePrintServiceRequest;
use Illuminate\Http\Request;

class PrintServiceController extends Controller
{
    /**
     * Display a listing of print services.
     */
    public function index(Request $request)
    {
        $listings = PrintService::with(['provider.user', 'area', 'service'])
            ->where('is_available', true)
            ->paginate($request->integer('per_page', 15));

        // Attempt to load base service details
        $service = null;
        if ($listings->isNotEmpty()) {
            $service = $listings->first()->service;
        } else {
            $service = Service::find(1); // 1 maps to print services
        }

        $data = $listings->map(fn($listing) => [
            'id'                             => $listing->id,
            'provider_name'                  => $listing->provider?->user?->name,
            'phone'                          => $listing->provider?->user?->phone,
            'title'                          => $listing->title,
            'image'                          => $listing->image ? asset('storage/' . $listing->image) : null,
            'area'                           => $listing->area?->name,
            'delivery_available'             => (bool) $listing->delevery_available,
            'has_color_option'               => (bool) $listing->has_color_option,
            'black_and_white_price_per_page' => $listing->black_and_white_price_per_page,
            'color_price_per_page'           => $listing->has_color_option ? $listing->color_price_per_page : null,
        ]);

        return response()->json([
            'status'  => true,
            'message' => 'تم استرجاع مزودي الخدمة بنجاح',
            'service' => $service ? [
                'id'    => $service->id,
                'title' => $service->title,
            ] : null,
            'data'    => $data,
            'meta'    => [
                'total'        => $listings->total(),
                'per_page'     => $listings->perPage(),
                'current_page' => $listings->currentPage(),
                'last_page'    => $listings->lastPage(),
            ],
        ]);
    }

    /**
     * Display the specified print service.
     */
    public function show(PrintService $printService)
    {
        $printService->load(['provider.user', 'area', 'service']);

        $user     = $printService->provider?->user;
        $provider = $printService->provider;
        $service  = $printService->service;

        return response()->json([
            'status'  => true,
            'message' => 'تم استرجاع تفاصيل الخدمة بنجاح',
            'service' => $service ? [
                'id'    => $service->id,
                'title' => $service->title,
            ] : null,
            'data' => [
                'id'                             => $printService->id,
                'title'                          => $printService->title,
                'description'                    => $printService->description,
                'image'                          => $printService->image ? asset('storage/' . $printService->image) : null,
                'area'                           => $printService->area?->name,
                'delivery_available'             => (bool) $printService->delevery_available,
                'has_color_option'               => (bool) $printService->has_color_option,
                'black_and_white_price_per_page' => $printService->black_and_white_price_per_page,
                'color_price_per_page'           => $printService->has_color_option ? $printService->color_price_per_page : null,
                'is_available'                   => (bool) $printService->is_available,
                'provider' => [
                    'id'      => $provider?->id,
                    'name'    => $user?->name,
                    'phone'   => $user?->phone,
                    'email'   => $user?->email,
                    'address' => $provider?->address,
                ],
            ],
        ]);
    }

    /**
     * Store a newly created print service in storage.
     */
    public function store(StorePrintServiceRequest $request)
    {
        $user = $request->user();
        $provider = $user->provider ?: \App\Models\Provider::create(['user_id' => $user->id]);

        $printServiceType = Service::where('title', 'خدمات الطباعة والتصوير')->first();
        $serviceId = $printServiceType ? $printServiceType->id : 1;

        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('print_services', 'public');
        }

        $printService = PrintService::create([
            'title'                          => $request->title,
            'description'                    => $request->description,
            'image'                          => $imagePath,
            'is_available'                   => true,
            'delevery_available'             => $request->boolean('delevery_available'),
            'has_color_option'               => $request->boolean('has_color_option'),
            'black_and_white_price_per_page' => $request->black_and_white_price_per_page,
            'color_price_per_page'           => $request->boolean('has_color_option') ? $request->color_price_per_page : 0.00,
            'provider_id'                    => $provider->id,
            'service_id'                     => $serviceId,
            'area_id'                        => $request->area_id,
        ]);

        $printService->load(['provider.user', 'area', 'service']);

        return response()->json([
            'status'  => true,
            'message' => 'تم إضافة خدمة الطباعة بنجاح',
            'data'    => [
                'id'                             => $printService->id,
                'title'                          => $printService->title,
                'description'                    => $printService->description,
                'image'                          => $printService->image ? asset('storage/' . $printService->image) : null,
                'area'                           => $printService->area?->name,
                'delivery_available'             => (bool) $printService->delevery_available,
                'has_color_option'               => (bool) $printService->has_color_option,
                'black_and_white_price_per_page' => $printService->black_and_white_price_per_page,
                'color_price_per_page'           => $printService->has_color_option ? $printService->color_price_per_page : null,
                'is_available'                   => (bool) $printService->is_available,
            ]
        ], 201);
    }
}
