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
            'status' => true,
            'message' => 'تم استرجاع الخدمات بنجاح',
            'data' =>
            $services->map(function ($services) {
                return [
                    'slug' => $services->slug,
                    'title' => $services->title,
                    'description' => $services->description,
                    'icon' => $services->icon ? asset('storage/' . $services->icon) : null,
                ];
            }),
            'meta' => [
                'total' => $services->total(),
                'per_page' => $services->perPage(),
                'current_page' => $services->currentPage(),
                'last_page' => $services->lastPage(),
            ]

        ]);
    }







    public function show(Service $service)
    {
        return response()->json([
            'status' => true,
            'message' => 'تم استرجاع الخدمة بنجاح',
            'data' => [
                'id' => $service->id,
                'slug' => $service->slug,
                'title' => $service->title,
                'description' => $service->description,
                'icon' => $service->icon ? asset('storage/' . $service->icon) : null,
                'status' => $service->status,
            ],
        ], 200);
    }





    public function provider(Service $service)
    {
        $providers = $service->with('providers')->get()->pluck('providers')->flatten();
        return response()->json([
            'status' => true,
            'message' => 'تم استرجاع مزودي الخدمة بنجاح',
            'data' => $providers->map(function ($provider) {
                return [
                    'id' => $provider->id,
                    'name' => $provider->name,

                ];
            }),
        ], 200);
    }
    
}
