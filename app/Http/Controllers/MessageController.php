<?php

namespace App\Http\Controllers;

use App\Models\Message;
use App\Models\User;
use App\Enums\UserTypeEnum;
use App\Http\Requests\Message\StoreMessageRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MessageController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Helper: Format a single message record for API response
    |--------------------------------------------------------------------------
    */
    private function formatMessage(Message $message): array
    {
        return [
            'id'          => $message->id,
            'message'     => $message->message,
            'sender_id'   => $message->sender_id,
            'receiver_id' => $message->receiver_id,
            'file_path'   => $message->file_path,
            'file_name'   => $message->file_name,
            'file_type'   => $message->file_type,
            'file_url'    => $message->file_path ? asset('storage/' . $message->file_path) : null,
            'sender'      => [
                'id'   => $message->sender?->id,
                'name' => $message->sender?->name,
                'type' => $message->sender?->type,
            ],
            'receiver'    => [
                'id'   => $message->receiver?->id,
                'name' => $message->receiver?->name,
                'type' => $message->receiver?->type,
            ],
            'read_at'     => $message->read_at,
            'created_at'  => $message->created_at,
            'updated_at'  => $message->updated_at,
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Helper: Check if user has permission to use messaging
    |--------------------------------------------------------------------------
    */
    private function authorizeMessaging(User $user): bool
    {
        return in_array($user->type, [
            UserTypeEnum::ADMIN,
            UserTypeEnum::PROVIDER,
            UserTypeEnum::RESIDENT,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | User: List all conversation chats (latest message per partner)
    |--------------------------------------------------------------------------
    | GET /v1/messages/chats
    */
    public function myConversations(Request $request)
    {
        $user = $request->user();
        if (!$this->authorizeMessaging($user)) {
            return response()->json([
                'status'  => false,
                'message' => 'غير مصرح لك باستخدام نظام الرسائل.',
            ], 403);
        }

        $authId = $user->id;

        // Get unique conversation partner IDs, ordered by their latest message time
        $partnerIdsQuery = Message::where('sender_id', $authId)
            ->orWhere('receiver_id', $authId)
            ->selectRaw('
                CASE WHEN sender_id = ? THEN receiver_id ELSE sender_id END as partner_id,
                MAX(created_at) as latest_message_time
            ', [$authId])
            ->groupBy('partner_id')
            ->orderByDesc('latest_message_time');

        $partnerIds = $partnerIdsQuery->pluck('partner_id')->toArray();

        if (empty($partnerIds)) {
            return response()->json([
                'status'  => true,
                'message' => 'لا توجد محادثات سابقة.',
                'data'    => [],
                'meta'    => [
                    'total'        => 0,
                    'per_page'     => $request->integer('per_page', 15),
                    'current_page' => 1,
                    'last_page'    => 1,
                ],
            ]);
        }

        $driver = DB::getDriverName();
        $query = User::whereIn('id', $partnerIds);

        if ($driver === 'mysql') {
            $implodedIds = implode(',', $partnerIds);
            $query->orderByRaw("FIELD(id, {$implodedIds})");
        } else {
            $cases = [];
            foreach ($partnerIds as $index => $id) {
                $cases[] = "WHEN id = {$id} THEN {$index}";
            }
            $casesStr = implode(' ', $cases);
            $query->orderByRaw("CASE {$casesStr} ELSE 9999 END");
        }

        $partners = $query->paginate($request->integer('per_page', 15));

        return response()->json([
            'status'  => true,
            'message' => 'تم استرجاع المحادثات بنجاح',
            'data'    => $partners->map(function ($partner) use ($authId) {
                $lastMessage = Message::where(function ($q) use ($authId, $partner) {
                        $q->where('sender_id', $authId)->where('receiver_id', $partner->id);
                    })->orWhere(function ($q) use ($authId, $partner) {
                        $q->where('sender_id', $partner->id)->where('receiver_id', $authId);
                    })
                    ->orderByDesc('created_at')
                    ->first();

                $unreadCount = Message::where('sender_id', $partner->id)
                    ->where('receiver_id', $authId)
                    ->whereNull('read_at')
                    ->count();

                return [
                    'partner' => [
                        'id'   => $partner->id,
                        'name' => $partner->name,
                        'email'=> $partner->email,
                        'type' => $partner->type,
                    ],
                    'last_message' => $lastMessage ? [
                        'id'         => $lastMessage->id,
                        'message'    => $lastMessage->message,
                        'sender_id'  => $lastMessage->sender_id,
                        'created_at' => $lastMessage->created_at,
                        'read_at'    => $lastMessage->read_at,
                    ] : null,
                    'unread_count' => $unreadCount,
                ];
            }),
            'meta'    => [
                'total'        => $partners->total(),
                'per_page'     => $partners->perPage(),
                'current_page' => $partners->currentPage(),
                'last_page'    => $partners->lastPage(),
            ],
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | User: View Chat History with a specific user (Marks as read)
    |--------------------------------------------------------------------------
    | GET /v1/messages/user/{partner}
    */
    public function chatHistory(Request $request, User $partner)
    {
        $user = $request->user();
        if (!$this->authorizeMessaging($user)) {
            return response()->json([
                'status'  => false,
                'message' => 'غير مصرح لك باستخدام نظام الرسائل.',
            ], 403);
        }

        if (!$this->authorizeMessaging($partner)) {
            return response()->json([
                'status'  => false,
                'message' => 'هذا المستخدم لا يمكن مراسلته.',
            ], 400);
        }

        $authId = $user->id;
        $partnerId = $partner->id;

        // Mark messages from this partner to the authenticated user as read
        Message::where('sender_id', $partnerId)
            ->where('receiver_id', $authId)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        // Get the messages between the two users
        $messages = Message::with(['sender', 'receiver'])
            ->where(function ($q) use ($authId, $partnerId) {
                $q->where('sender_id', $authId)->where('receiver_id', $partnerId);
            })
            ->orWhere(function ($q) use ($authId, $partnerId) {
                $q->where('sender_id', $partnerId)->where('receiver_id', $authId);
            })
            ->orderByDesc('created_at')
            ->paginate($request->integer('per_page', 30));

        return response()->json([
            'status'  => true,
            'message' => 'تم استرجاع سجل الرسائل بنجاح',
            'data'    => $messages->map(fn($msg) => $this->formatMessage($msg)),
            'meta'    => [
                'total'        => $messages->total(),
                'per_page'     => $messages->perPage(),
                'current_page' => $messages->currentPage(),
                'last_page'    => $messages->lastPage(),
            ],
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | User: Send a new message
    |--------------------------------------------------------------------------
    | POST /v1/messages
    */
    public function store(StoreMessageRequest $request)
    {
        $filePath = null;
        $fileName = null;
        $fileType = null;

        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $filePath = $file->store('chats', 'public');
            $fileName = $file->getClientOriginalName();
            $fileType = $file->getClientMimeType();
        }

        $message = Message::create([
            'sender_id'   => $request->user()->id,
            'receiver_id' => $request->receiver_id,
            'message'     => $request->message,
            'file_path'   => $filePath,
            'file_name'   => $fileName,
            'file_type'   => $fileType,
        ]);

        $message->load(['sender', 'receiver']);

        return response()->json([
            'status'  => true,
            'message' => 'تم إرسال الرسالة بنجاح',
            'data'    => $this->formatMessage($message),
        ], 201);
    }

    /*
    |--------------------------------------------------------------------------
    | Admin: List all messages in the system (Moderation)
    |--------------------------------------------------------------------------
    | GET /v1/admin/messages
    */
    public function adminIndex(Request $request)
    {
        $query = Message::with(['sender', 'receiver']);

        if ($request->filled('sender_id')) {
            $query->where('sender_id', $request->sender_id);
        }

        if ($request->filled('receiver_id')) {
            $query->where('receiver_id', $request->receiver_id);
        }

        if ($request->filled('search')) {
            $query->where('message', 'like', '%' . $request->search . '%');
        }

        $messages = $query->orderByDesc('created_at')
            ->paginate($request->integer('per_page', 15));

        return response()->json([
            'status'  => true,
            'message' => 'تم استرجاع جميع الرسائل بنجاح',
            'data'    => $messages->map(fn($msg) => $this->formatMessage($msg)),
            'meta'    => [
                'total'        => $messages->total(),
                'per_page'     => $messages->perPage(),
                'current_page' => $messages->currentPage(),
                'last_page'    => $messages->lastPage(),
            ],
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Admin: Delete a message (Moderation)
    |--------------------------------------------------------------------------
    | DELETE /v1/admin/messages/{message}
    */
    public function adminDestroy(Message $message)
    {
        if ($message->file_path) {
            \Illuminate\Support\Facades\Storage::disk('public')->delete($message->file_path);
        }

        $message->delete();

        return response()->json([
            'status'  => true,
            'message' => 'تم حذف الرسالة بنجاح',
        ]);
    }
}
