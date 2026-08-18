<?php

namespace App\Http\Controllers;

use App\Enums\UserTypeEnum;
use App\Models\Provider;
use App\Models\User;
use App\Services\ActiveDeviceService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Admin: List all users with pagination and search/filtering
    |--------------------------------------------------------------------------
    | GET /v1/admin/users
    */
    public function index(Request $request)
    {
        $query = User::query();

        // Filter by role/type
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        // Filter by block status
        if ($request->has('is_blocked')) {
            $query->where('is_blocked', filter_var($request->is_blocked, FILTER_VALIDATE_BOOLEAN));
        }

        // Search by name, email, or phone
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        $users = $query->orderByDesc('created_at')
            ->paginate($request->integer('per_page', 15));

        return response()->json([
            'status' => true,
            'message' => 'تم استرجاع قائمة المستخدمين بنجاح',
            'data' => $users->map(fn ($user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'type' => $user->type?->value ?? $user->type,
                'is_blocked' => (bool) $user->is_blocked,
                'created_at' => $user->created_at,
            ]),
            'meta' => [
                'total' => $users->total(),
                'per_page' => $users->perPage(),
                'current_page' => $users->currentPage(),
                'last_page' => $users->lastPage(),
            ],
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Admin: Create a new user
    |--------------------------------------------------------------------------
    | POST /v1/admin/users
    */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['required', 'string', 'max:20', 'unique:users,phone'],
            'password' => ['required', 'string', 'min:6'],
            'type' => ['required', Rule::enum(UserTypeEnum::class)],
        ], [
            'name.required' => 'اسم المستخدم مطلوب.',
            'email.required' => 'البريد الإلكتروني مطلوب.',
            'email.unique' => 'البريد الإلكتروني مستخدم بالفعل.',
            'phone.required' => 'رقم الهاتف مطلوب.',
            'phone.unique' => 'رقم الهاتف مستخدم بالفعل.',
            'password.required' => 'كلمة المرور مطلوبة.',
            'type.required' => 'نوع المستخدم مطلوب.',
        ]);

        $user = User::create($validated);

        // If type is provider, create Provider record if needed
        if ($user->type === UserTypeEnum::PROVIDER) {
            Provider::firstOrCreate(['user_id' => $user->id]);
        }

        return response()->json([
            'status' => true,
            'message' => 'تم إنشاء المستخدم بنجاح',
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'type' => $user->type?->value ?? $user->type,
                'is_blocked' => (bool) $user->is_blocked,
                'created_at' => $user->created_at,
            ],
        ], 201);
    }

    /*
    |--------------------------------------------------------------------------
    | Admin: Show user details
    |--------------------------------------------------------------------------
    | GET /v1/admin/users/{user}
    */
    public function show(User $user)
    {
        $user->load(['properties', 'bed.room.property', 'contactMessages', 'activeDevice']);

        return response()->json([
            'status' => true,
            'message' => 'تم استرجاع بيانات المستخدم بنجاح',
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'type' => $user->type?->value ?? $user->type,
                'is_blocked' => (bool) $user->is_blocked,
                'created_at' => $user->created_at,
                'residence' => $user->bed ? [
                    'bed_id' => $user->bed->id,
                    'room' => $user->bed->room?->name,
                    'property' => $user->bed->room?->property?->title,
                ] : null,
                'properties_count' => $user->properties->count(),
                'active_device' => $user->activeDevice ? [
                    'login_at' => $user->activeDevice->created_at,
                    'last_activity_at' => $user->activeDevice->last_activity_at,
                    'status' => $user->activeDevice->revoked_at ? 'revoked' : 'active',
                ] : null,
            ],
        ]);
    }

    public function revokeDevice(User $user, ActiveDeviceService $activeDevices)
    {
        $revoked = $activeDevices->revoke($user);

        return response()->json([
            'success' => true,
            'message' => $revoked ? 'The active device was revoked.' : 'The user has no active device.',
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Admin: Update user details
    |--------------------------------------------------------------------------
    | PUT /v1/admin/users/{user}
    */
    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'string', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'phone' => ['sometimes', 'string', 'max:20', Rule::unique('users')->ignore($user->id)],
            'password' => ['nullable', 'string', 'min:6'],
            'type' => ['sometimes', Rule::enum(UserTypeEnum::class)],
        ]);

        if (empty($validated['password'])) {
            unset($validated['password']);
        }

        $user->update($validated);

        if ($user->type === UserTypeEnum::PROVIDER) {
            Provider::firstOrCreate(['user_id' => $user->id]);
        }

        return response()->json([
            'status' => true,
            'message' => 'تم تحديث بيانات المستخدم بنجاح',
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'type' => $user->type?->value ?? $user->type,
                'is_blocked' => (bool) $user->is_blocked,
                'updated_at' => $user->updated_at,
            ],
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Admin: Toggle user block status (Block / Unblock)
    |--------------------------------------------------------------------------
    | PATCH /v1/admin/users/{user}/block
    */
    public function toggleBlock(User $user)
    {
        $user->update([
            'is_blocked' => ! $user->is_blocked,
        ]);

        $messageText = $user->is_blocked ? 'تم حظر المستخدم بنجاح' : 'تم إلغاء حظر المستخدم بنجاح';

        return response()->json([
            'status' => true,
            'message' => $messageText,
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'is_blocked' => (bool) $user->is_blocked,
            ],
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Admin: Delete user
    |--------------------------------------------------------------------------
    | DELETE /v1/admin/users/{user}
    */
    public function destroy(User $user)
    {
        $user->delete();

        return response()->json([
            'status' => true,
            'message' => 'تم حذف المستخدم بنجاح',
        ]);
    }
}
