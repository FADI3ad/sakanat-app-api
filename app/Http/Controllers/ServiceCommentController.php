<?php

namespace App\Http\Controllers;

use App\Models\Service;
use App\Models\ServiceComment;
use App\Http\Requests\Comment\StoreCommentRequest;
use App\Enums\UserTypeEnum;
use Illuminate\Http\Request;

class ServiceCommentController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Public / Provider: List active comments for a service
    |--------------------------------------------------------------------------
    | GET /v1/services/{service}/comments
    |
    | - Residents (service seekers) see only active comments
    | - Providers (service owners) see all comments on their own services
    | - Admin sees everything
    */
    public function index(Request $request, Service $service)
    {
        $user = $request->user();

        // Build query: provider of THIS service can see all comments (active + inactive)
        $query = ServiceComment::where('service_id', $service->id)->with('user');

        $isOwner = $user
            && $user->type === UserTypeEnum::PROVIDER
            && $user->provider
            && $service->provider_id === $user->provider->id;

        $isAdmin = $user && $user->type === UserTypeEnum::ADMIN;

        // Only the service owner and admin see hidden (inactive) comments
        if (!$isOwner && !$isAdmin) {
            $query->where('is_active', true);
        }

        $comments = $query->orderByDesc('created_at')
            ->paginate($request->integer('per_page', 15));

        return response()->json([
            'status'  => true,
            'message' => 'تم استرجاع التعليقات بنجاح',
            'data'    => $comments->map(fn($comment) => [
                'id'         => $comment->id,
                'body'       => $comment->body,
                'is_active'  => (bool) $comment->is_active,
                'created_at' => $comment->created_at,
                'user'       => [
                    'id'   => $comment->user?->id,
                    'name' => $comment->user?->name,
                ],
            ]),
            'meta' => [
                'total'        => $comments->total(),
                'per_page'     => $comments->perPage(),
                'current_page' => $comments->currentPage(),
                'last_page'    => $comments->lastPage(),
            ],
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Resident (Service Seeker): Add a comment to a service
    |--------------------------------------------------------------------------
    | POST /v1/services/{service}/comments
    */
    public function store(StoreCommentRequest $request, Service $service)
    {
        $user = $request->user();

        // Only residents (service seekers) can comment, not providers
        if ($user->type === UserTypeEnum::PROVIDER) {
            return response()->json([
                'status'  => false,
                'message' => 'مزودو الخدمات لا يمكنهم إضافة تعليقات على الخدمات.',
            ], 403);
        }

        $comment = ServiceComment::create([
            'service_id' => $service->id,
            'user_id'    => $user->id,
            'body'       => $request->validated()['body'],
        ]);

        return response()->json([
            'status'  => true,
            'message' => 'تم إضافة تعليقك بنجاح',
            'data'    => [
                'id'         => $comment->id,
                'body'       => $comment->body,
                'is_active'  => (bool) $comment->is_active,
                'created_at' => $comment->created_at,
                'user'       => [
                    'id'   => $user->id,
                    'name' => $user->name,
                ],
            ],
        ], 201);
    }

    /*
    |--------------------------------------------------------------------------
    | Admin: Delete (or deactivate) a comment
    |--------------------------------------------------------------------------
    | DELETE /v1/admin/comments/{comment}
    */
    public function destroy(ServiceComment $serviceComment)
    {
        $serviceComment->delete();

        return response()->json([
            'status'  => true,
            'message' => 'تم حذف التعليق بنجاح',
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Admin: Toggle comment visibility (activate / deactivate)
    |--------------------------------------------------------------------------
    | PATCH /v1/admin/comments/{comment}/toggle
    */
    public function toggle(ServiceComment $serviceComment)
    {
        $serviceComment->update([
            'is_active' => !$serviceComment->is_active,
        ]);

        $statusText = $serviceComment->is_active ? 'تم تفعيل التعليق' : 'تم إخفاء التعليق';

        return response()->json([
            'status'  => true,
            'message' => $statusText,
            'data'    => [
                'id'        => $serviceComment->id,
                'is_active' => (bool) $serviceComment->is_active,
            ],
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Admin: List ALL comments (across all services) for moderation
    |--------------------------------------------------------------------------
    | GET /v1/admin/comments
    */
    public function adminIndex(Request $request)
    {
        $query = ServiceComment::with(['user', 'service']);

        // Filter by status
        if ($request->filled('is_active')) {
            $query->where('is_active', filter_var($request->is_active, FILTER_VALIDATE_BOOLEAN));
        }

        // Filter by service
        if ($request->filled('service_id')) {
            $query->where('service_id', $request->integer('service_id'));
        }

        $comments = $query->orderByDesc('created_at')
            ->paginate($request->integer('per_page', 15));

        return response()->json([
            'status'  => true,
            'message' => 'تم استرجاع التعليقات بنجاح',
            'data'    => $comments->map(fn($comment) => [
                'id'         => $comment->id,
                'body'       => $comment->body,
                'is_active'  => (bool) $comment->is_active,
                'created_at' => $comment->created_at,
                'service'    => [
                    'id'    => $comment->service?->id,
                    'title' => $comment->service?->title,
                ],
                'user'       => [
                    'id'   => $comment->user?->id,
                    'name' => $comment->user?->name,
                    'type' => $comment->user?->type,
                ],
            ]),
            'meta' => [
                'total'        => $comments->total(),
                'per_page'     => $comments->perPage(),
                'current_page' => $comments->currentPage(),
                'last_page'    => $comments->lastPage(),
            ],
        ]);
    }
}
