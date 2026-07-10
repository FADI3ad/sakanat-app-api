<?php

namespace App\Http\Controllers;

use App\Models\Service;
use Illuminate\Http\Request;

class ServiceController extends Controller
{

    public function index()
    {
        $services = Service::where('status', true)->orderBy('sort_order')->paginate(10);

        return response()->json([
            'status'  => true,
            'message' => 'تم استرجاع الخدمات بنجاح',
            'data'    => $services->map(fn($s) => [
                'id'          => $s->id,
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


    public function show(Service $service)
    {
        return response()->json([
            'status'  => true,
            'message' => 'تم استرجاع الخدمة بنجاح',
            'data'    => [
                'id'          => $service->id,
                'title'       => $service->title,
                'description' => $service->description,
                'icon'        => $service->icon ? asset('storage/' . $service->icon) : null,
                'status'      => $service->status,
            ],
        ]);
    }

    public function listings(Service $service, Request $request)
    {
        if ($service->id === 1 || $service->title === 'خدمات الطباعة والتصوير') {
            return app(PrintServiceController::class)->index($request);
        }

        return response()->json([
            'status'  => false,
            'message' => 'هذه الخدمة غير متوفرة حالياً',
            'data'    => [],
        ], 404);
    }

    public function listingDetails(Service $service, $listingId)
    {
        if ($service->id === 1 || $service->title === 'خدمات الطباعة والتصوير') {
            $printService = \App\Models\PrintService::findOrFail($listingId);
            return app(PrintServiceController::class)->show($printService);
        }

        return response()->json([
            'status'  => false,
            'message' => 'هذه الخدمة غير متوفرة حالياً',
            'data'    => [],
        ], 404);
    }

}
