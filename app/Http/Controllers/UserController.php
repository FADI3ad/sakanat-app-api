<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Provider;
use App\Models\Type;
use App\Models\Service;
use App\Enums\UserTypeEnum;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;

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
            'status'  => true,
            'message' => 'تم استرجاع قائمة المستخدمين بنجاح',
            'data'    => $users->map(fn($user) => [
                'id'         => $user->id,
                'name'       => $user->name,
                'email'      => $user->email,
                'phone'      => $user->phone,
                'type'       => $user->type?->value ?? $user->type,
                'is_blocked' => (bool) $user->is_blocked,
                'created_at' => $user->created_at,
            ]),
            'meta'    => [
                'total'        => $users->total(),
                'per_page'     => $users->perPage(),
                'current_page' => $users->currentPage(),
                'last_page'    => $users->lastPage(),
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
        $isProvider = $request->input('type') === UserTypeEnum::PROVIDER->value || $request->input('type') === 'provider';

        $validated = $request->validate([
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'phone'    => ['required', 'string', 'max:20', 'unique:users,phone'],
            'password' => ['required', 'string', 'min:6'],
            'type'     => ['required', Rule::enum(UserTypeEnum::class)],
            'type_id'  => [$isProvider ? 'required' : 'nullable', 'integer', 'exists:types,id'],
        ], [
            'name.required'     => 'اسم المستخدم مطلوب.',
            'email.required'    => 'البريد الإلكتروني مطلوب.',
            'email.unique'      => 'البريد الإلكتروني مستخدم بالفعل.',
            'phone.required'    => 'رقم الهاتف مطلوب.',
            'phone.unique'      => 'رقم الهاتف مستخدم بالفعل.',
            'password.required' => 'كلمة المرور مطلوبة.',
            'type.required'     => 'نوع المستخدم مطلوب.',
            'type_id.required'  => 'نوع الخدمة (type_id) مطلوب عند إنشاء حساب مزود خدمة.',
            'type_id.integer'   => 'معرّف نوع الخدمة يجب أن يكون رقماً صحيحاً.',
            'type_id.exists'    => 'نوع الخدمة المحدد غير موجود.',
        ]);

        $userData = collect($validated)->except('type_id')->toArray();
        $user = User::create($userData);

        $providerData = null;
        if ($user->type === UserTypeEnum::PROVIDER) {
            $provider = Provider::updateOrCreate(
                ['user_id' => $user->id],
                ['type_id' => $request->input('type_id')]
            );
            $provider->load('type');
            $providerData = [
                'id'        => $provider->id,
                'type_id'   => $provider->type_id,
                'type_name' => $provider->type?->name,
            ];
        }

        return response()->json([
            'status'  => true,
            'message' => 'تم إنشاء المستخدم بنجاح',
            'data'    => array_merge([
                'id'         => $user->id,
                'name'       => $user->name,
                'email'      => $user->email,
                'phone'      => $user->phone,
                'type'       => $user->type?->value ?? $user->type,
                'is_blocked' => (bool) $user->is_blocked,
                'created_at' => $user->created_at,
            ], $providerData ? ['provider' => $providerData] : []),
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
        $user->load(['provider.type', 'properties', 'bed.room.property', 'contactMessages']);

        $providerData = null;
        if ($user->type === UserTypeEnum::PROVIDER && $user->provider) {
            $providerData = [
                'id'        => $user->provider->id,
                'type_id'   => $user->provider->type_id,
                'type_name' => $user->provider->type?->name,
            ];
        }

        return response()->json([
            'status'  => true,
            'message' => 'تم استرجاع بيانات المستخدم بنجاح',
            'data'    => array_merge([
                'id'         => $user->id,
                'name'       => $user->name,
                'email'      => $user->email,
                'phone'      => $user->phone,
                'type'       => $user->type?->value ?? $user->type,
                'is_blocked' => (bool) $user->is_blocked,
                'created_at' => $user->created_at,
                'residence'  => $user->bed ? [
                    'bed_id'   => $user->bed->id,
                    'room'     => $user->bed->room?->name,
                    'property' => $user->bed->room?->property?->title,
                ] : null,
                'properties_count' => $user->properties->count(),
            ], $providerData ? ['provider' => $providerData] : []),
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
            'name'     => ['sometimes', 'string', 'max:255'],
            'email'    => ['sometimes', 'string', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'phone'    => ['sometimes', 'string', 'max:20', Rule::unique('users')->ignore($user->id)],
            'password' => ['nullable', 'string', 'min:6'],
            'type'     => ['sometimes', Rule::enum(UserTypeEnum::class)],
            'type_id'  => ['nullable', 'integer', 'exists:types,id'],
        ], [
            'type_id.integer' => 'معرّف نوع الخدمة يجب أن يكون رقماً صحيحاً.',
            'type_id.exists'  => 'نوع الخدمة المحدد غير موجود.',
        ]);

        if (empty($validated['password'])) {
            unset($validated['password']);
        }

        $userData = collect($validated)->except('type_id')->toArray();
        $user->update($userData);

        $providerData = null;
        if ($user->type === UserTypeEnum::PROVIDER) {
            $provider = Provider::updateOrCreate(
                ['user_id' => $user->id],
                $request->has('type_id') ? ['type_id' => $request->input('type_id')] : []
            );
            $provider->load('type');
            $providerData = [
                'id'        => $provider->id,
                'type_id'   => $provider->type_id,
                'type_name' => $provider->type?->name,
            ];
        }

        return response()->json([
            'status'  => true,
            'message' => 'تم تحديث بيانات المستخدم بنجاح',
            'data'    => array_merge([
                'id'         => $user->id,
                'name'       => $user->name,
                'email'      => $user->email,
                'phone'      => $user->phone,
                'type'       => $user->type?->value ?? $user->type,
                'is_blocked' => (bool) $user->is_blocked,
                'updated_at' => $user->updated_at,
            ], $providerData ? ['provider' => $providerData] : []),
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
            'is_blocked' => !$user->is_blocked,
        ]);

        $messageText = $user->is_blocked ? 'تم حظر المستخدم بنجاح' : 'تم إلغاء حظر المستخدم بنجاح';

        return response()->json([
            'status'  => true,
            'message' => $messageText,
            'data'    => [
                'id'         => $user->id,
                'name'       => $user->name,
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
            'status'  => true,
            'message' => 'تم حذف المستخدم بنجاح',
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Admin: Create user account directly with service & category (type)
    |--------------------------------------------------------------------------
    | POST /v1/admin/users/provider-with-service
    */
    public function storeProviderWithService(Request $request)
    {
        $validated = $request->validate([
            // User Data
            'name'                => ['required', 'string', 'max:255'],
            'email'               => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'phone'               => ['required', 'string', 'max:20', 'unique:users,phone'],
            'password'            => ['required', 'string', 'min:6'],
            'type'                => ['nullable', Rule::enum(UserTypeEnum::class)],

            // Service Type (Category) Data - Must exist in types table
            'type_id'             => ['required', 'integer', 'exists:types,id'],

            // Service Data
            'title'               => ['required', 'string', 'max:255'],
            'description'         => ['nullable', 'string'],
            'price'               => ['required', 'numeric', 'min:0'],
            'area_id'             => ['required', 'integer', 'exists:areas,id'],
            'delevery_available'  => ['nullable', 'boolean'],
            'delivery_available'  => ['nullable', 'boolean'],
            'is_available'        => ['nullable', 'boolean'],
            'image'               => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:2048'],
        ], [
            'name.required'       => 'اسم المستخدم مطلوب.',
            'email.required'      => 'البريد الإلكتروني مطلوب.',
            'email.unique'        => 'البريد الإلكتروني مستخدم بالفعل.',
            'phone.required'      => 'رقم الهاتف مطلوب.',
            'phone.unique'        => 'رقم الهاتف مستخدم بالفعل.',
            'password.required'   => 'كلمة المرور مطلوبة.',
            'type.enum'           => 'نوع المستخدم غير صالح.',
            'type_id.required'    => 'نوع الخدمة (type_id) مطلوب.',
            'type_id.integer'     => 'معرّف نوع الخدمة يجب أن يكون رقماً صحيحاً.',
            'type_id.exists'      => 'نوع الخدمة المحدد غير موجود في جدول أنواع الخدمات.',
            'title.required'      => 'عنوان الخدمة مطلوب.',
            'price.required'      => 'سعر الخدمة مطلوب.',
            'price.numeric'       => 'سعر الخدمة يجب أن يكون رقماً.',
            'price.min'           => 'سعر الخدمة لا يمكن أن يكون بالسالب.',
            'area_id.required'    => 'المنطقة مطلوبة.',
            'area_id.exists'      => 'المنطقة المحددة غير موجودة.',
            'image.image'         => 'الملف المرفوع للخدمة يجب أن يكون صورة.',
            'image.mimes'         => 'صورة الخدمة يجب أن تكون من نوع: jpeg, png, jpg, webp.',
            'image.max'           => 'حجم الصورة يجب ألا يتجاوز 2 ميجابايت.',
        ]);

        $userType = $validated['type'] ?? UserTypeEnum::PROVIDER->value;
        $deliveryAvailable = $request->boolean('delevery_available', $request->boolean('delivery_available', false));

        $result = DB::transaction(function () use ($request, $validated, $userType, $deliveryAvailable) {
            // 1. Create User with specified type (defaults to provider if not provided)
            $user = User::create([
                'name'     => $validated['name'],
                'email'    => $validated['email'],
                'phone'    => $validated['phone'],
                'password' => $validated['password'],
                'type'     => $userType,
            ]);

            // 2. Get existing Service Type
            $type = Type::findOrFail($validated['type_id']);

            // 3. Create Provider record for the user with assigned type_id
            $provider = Provider::updateOrCreate(
                ['user_id' => $user->id],
                ['type_id' => $type->id]
            );

            // 4. Handle Service Image Upload
            $imagePath = null;
            if ($request->hasFile('image')) {
                $imagePath = $request->file('image')->store('services', 'public');
            }

            // 5. Create Service
            $service = Service::create([
                'title'              => $validated['title'],
                'description'        => $validated['description'] ?? null,
                'image'              => $imagePath,
                'price'              => $validated['price'],
                'is_available'       => $request->boolean('is_available', true),
                'delevery_available' => $deliveryAvailable,
                'provider_id'        => $provider->id,
                'area_id'            => $validated['area_id'],
                'type_id'            => $type->id,
            ]);

            $service->load(['area']);

            return compact('user', 'provider', 'type', 'service');
        });

        return response()->json([
            'status'  => true,
            'message' => 'تم إنشاء حساب المستخدم وإضافة الخدمة بنجاح',
            'data'    => [
                'user' => [
                    'id'         => $result['user']->id,
                    'name'       => $result['user']->name,
                    'email'      => $result['user']->email,
                    'phone'      => $result['user']->phone,
                    'type'       => $result['user']->type?->value ?? $result['user']->type,
                    'is_blocked' => (bool) $result['user']->is_blocked,
                    'created_at' => $result['user']->created_at,
                ],
                'provider' => [
                    'id'      => $result['provider']->id,
                    'user_id' => $result['provider']->user_id,
                ],
                'type' => [
                    'id'          => $result['type']->id,
                    'name'        => $result['type']->name,
                    'description' => $result['type']->description,
                ],
                'service' => [
                    'id'                 => $result['service']->id,
                    'title'              => $result['service']->title,
                    'description'        => $result['service']->description,
                    'image'              => $result['service']->image ? asset('storage/' . $result['service']->image) : null,
                    'price'              => $result['service']->price,
                    'is_available'       => (bool) $result['service']->is_available,
                    'delivery_available' => (bool) $result['service']->delevery_available,
                    'area'               => $result['service']->area?->name,
                    'created_at'         => $result['service']->created_at,
                ],
            ],
        ], 201);
    }
}

