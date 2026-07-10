<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Enums\UserTypeEnum;

class EnsureUserIsProvider
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!$request->user() || $request->user()->type !== UserTypeEnum::PROVIDER) {
            return response()->json([
                'status'  => false,
                'message' => 'غير مصرح لك بالوصول، يجب أن تكون مقدم خدمة.',
            ], 403);
        }

        return $next($request);
    }
}
