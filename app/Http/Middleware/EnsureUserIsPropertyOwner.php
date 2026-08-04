<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Enums\UserTypeEnum;

class EnsureUserIsPropertyOwner
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!$request->user() || !in_array($request->user()->type, [UserTypeEnum::PROPERTY_OWNER, UserTypeEnum::ADMIN])) {
            return response()->json([
                'status'  => false,
                'message' => 'غير مصرح لك بالوصول، يجب أن تكون مالك عقار.',
            ], 403);
        }

        return $next($request);
    }
}
