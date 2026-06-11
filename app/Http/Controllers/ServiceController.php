<?php

namespace App\Http\Controllers;

use App\Models\Service;
use Illuminate\Http\Request;

class ServiceController extends Controller
{
    /**
     * GET /v1/services
     * List all active services (the base service types).
     */
    public function index()
    {
        $services = Service::where('status', true)->orderBy('sort_order')->paginate(10);

        return response()->json([
            'status'  => true,
            'message' => 'تم استرجاع الخدمات بنجاح',
            'data'    => $services->map(fn($s) => [
                'slug'        => $s->slug,
                'title'       => $s->title,
                'description' => $s->description,
                'icon'        => $s->icon ? asset('storage/' . $s->icon) : null,
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
     * GET /v1/services/{service}
     * Show details of a single base service (resolved by slug via route model binding).
     */
    public function show(Service $service)
    {
        return response()->json([
            'status'  => true,
            'message' => 'تم استرجاع الخدمة بنجاح',
            'data'    => [
                'slug'        => $service->slug,
                'title'       => $service->title,
                'description' => $service->description,
                'icon'        => $service->icon ? asset('storage/' . $service->icon) : null,
                'status'      => $service->status,
            ],
        ]);
    }

    // =========================================================================
    // GET /v1/services/{service}/listings
    // =========================================================================


    public function getServiceListings(Service $service, Request $request)
    {
        
        $modelClass = $service->resolveServiceModel();

        if (!$modelClass) {
            return response()->json([
                'status'  => false,
                'message' => 'هذه الخدمة غير مدعومة بعد أو لا يوجد مزودون لها.',
            ], 404);
        }

      
        $formatters = [
            \Illuminate\Support\Str::slug('خدمات الطباعة والتصوير') => 'formatPrintServiceListing',
            // \Illuminate\Support\Str::slug('خدمات الغسيل والمكواة') => 'formatLaundryServiceListing',
            // \Illuminate\Support\Str::slug('خدمات المواصلات')       => 'formatTransportServiceListing',
        ];

        $formatterMethod = $formatters[$service->slug] ?? 'formatDefaultListing';

        $listings = $modelClass::with(['provider.user', 'area'])
            ->where('service_id', $service->id)
            ->where('is_available', true)
            ->paginate($request->integer('per_page', 15));

        $data = $listings->map(fn($listing) => $this->$formatterMethod($listing));

        return response()->json([
            'status'  => true,
            'message' => 'تم استرجاع مزودي الخدمة بنجاح',
            'service' => [
                'slug'  => $service->slug,
                'title' => $service->title,
            ],
            'data' => $data,
            'meta' => [
                'total'        => $listings->total(),
                'per_page'     => $listings->perPage(),
                'current_page' => $listings->currentPage(),
                'last_page'    => $listings->lastPage(),
            ],
        ]);
    }

    // =========================================================================
    // GET /v1/services/{service}/listings/{listing}
    // =========================================================================

    public function showListing(Service $service, int $listing)
    {
        $modelClass = $service->resolveServiceModel();

        if (!$modelClass) {
            return response()->json([
                'status'  => false,
                'message' => 'هذه الخدمة غير مدعومة بعد أو لا يوجد مزودون لها.',
            ], 404);
        }

        $detailFormatters = [
            \Illuminate\Support\Str::slug('خدمات الطباعة والتصوير') => 'formatPrintServiceDetail',
        ];

        $formatterMethod = $detailFormatters[$service->slug] ?? 'formatDefaultDetail';

        $record = $modelClass::with(['provider.user', 'area', 'service'])
            ->where('service_id', $service->id)
            ->findOrFail($listing);

        return response()->json([
            'status'  => true,
            'message' => 'تم استرجاع تفاصيل الخدمة بنجاح',
            'service' => [
                'slug'  => $service->slug,
                'title' => $service->title,
            ],
            'data' => $this->$formatterMethod($record),
        ]);
    }


    private function formatPrintServiceListing($listing): array
    {
        $user = $listing->provider?->user;

        return [
            'id'                             => $listing->id,
            'provider_name'                  => $user?->name,
            'phone'                          => $user?->phone,
            'title'                          => $listing->title,
            'image'                          => $listing->image ? asset('storage/' . $listing->image) : null,
            'area'                           => $listing->area?->name,
            'delivery_available'             => (bool) $listing->delevery_available,
            'has_color_option'               => (bool) $listing->has_color_option,
            'black_and_white_price_per_page' => $listing->black_and_white_price_per_page,
            'color_price_per_page'           => $listing->has_color_option ? $listing->color_price_per_page : null,
        ];
    }

    /**
     * Full details for a single print-service listing.
     */
    private function formatPrintServiceDetail($listing): array
    {
        $user     = $listing->provider?->user;
        $provider = $listing->provider;

        return [
            'id'                             => $listing->id,
            'title'                          => $listing->title,
            'description'                    => $listing->description,
            'image'                          => $listing->image ? asset('storage/' . $listing->image) : null,
            'area'                           => $listing->area?->name,
            'delivery_available'             => (bool) $listing->delevery_available,
            'has_color_option'               => (bool) $listing->has_color_option,
            'black_and_white_price_per_page' => $listing->black_and_white_price_per_page,
            'color_price_per_page'           => $listing->has_color_option ? $listing->color_price_per_page : null,
            'is_available'                   => (bool) $listing->is_available,
            'provider' => [
                'id'      => $provider?->id,
                'name'    => $user?->name,
                'phone'   => $user?->phone,
                'email'   => $user?->email,
                'address' => $provider?->address,
            ],
        ];
    }


    private function formatDefaultListing($listing): array
    {
        $user = $listing->provider?->user;

        return [
            'id'            => $listing->id,
            'provider_name' => $user?->name,
            'phone'         => $user?->phone,
            'title'         => $listing->title,
            'image'         => $listing->image ? asset('storage/' . $listing->image) : null,
            'area'          => $listing->area?->name,
        ];
    }

    /**
     * Fallback full-detail formatter for services without a dedicated one.
     */
    private function formatDefaultDetail($listing): array
    {
        $user     = $listing->provider?->user;
        $provider = $listing->provider;

        return [
            'id'          => $listing->id,
            'title'       => $listing->title,
            'description' => $listing->description ?? null,
            'image'       => $listing->image ? asset('storage/' . $listing->image) : null,
            'area'        => $listing->area?->name,
            'is_available' => (bool) ($listing->is_available ?? true),
            'provider' => [
                'id'      => $provider?->id,
                'name'    => $user?->name,
                'phone'   => $user?->phone,
                'email'   => $user?->email,
                'address' => $provider?->address,
            ],
        ];
    }
}
