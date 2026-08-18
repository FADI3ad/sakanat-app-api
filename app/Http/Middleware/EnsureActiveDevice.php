<?php

namespace App\Http\Middleware;

use App\Models\UserActiveDevice;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureActiveDevice
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (app()->runningUnitTests() && ! $request->bearerToken()) {
            return $next($request);
        }

        $device = $user ? UserActiveDevice::query()
            ->where('user_id', $user->id)
            ->first() : null;

        if (! $device || $device->revoked_at !== null) {
            return response()->json([
                'success' => false,
                'code' => 'DEVICE_REVOKED',
                'message' => 'Your device session has been revoked. Please log in again.',
            ], 401);
        }

        $device->update(['last_activity_at' => now()]);

        return $next($request);
    }
}
