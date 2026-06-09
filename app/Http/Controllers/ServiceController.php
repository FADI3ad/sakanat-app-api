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
                    'image' => $services->image ? asset('storage/' . $services->image) : null,
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
                'image' => $service->image ? asset('storage/' . $service->image) : null,
                'status' => $service->status,
            ],
        ], 200);
    }
}
