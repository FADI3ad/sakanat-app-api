<?php

namespace App\Http\Controllers;

use App\Models\ContactMessage;
use App\Http\Requests\Contact\StoreContactRequest;
use Illuminate\Http\Request;

class ContactController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | User: Send a contact message to admin
    |--------------------------------------------------------------------------
    | POST /v1/contact
    */
    public function store(StoreContactRequest $request)
    {
        $message = ContactMessage::create([
            'user_id' => $request->user()->id,
            'subject' => $request->validated()['subject'],
            'message' => $request->validated()['message'],
        ]);

        return response()->json([
            'status'  => true,
            'message' => 'تم إرسال رسالتك بنجاح، سيتواصل معك الفريق قريباً.',
            'data'    => [
                'id'         => $message->id,
                'subject'    => $message->subject,
                'message'    => $message->message,
                'status'     => $message->status,
                'created_at' => $message->created_at,
            ],
        ], 201);
    }

    /*
    |--------------------------------------------------------------------------
    | User: View their own contact messages
    |--------------------------------------------------------------------------
    | GET /v1/contact/my
    */
    public function myMessages(Request $request)
    {
        $messages = ContactMessage::where('user_id', $request->user()->id)
            ->orderByDesc('created_at')
            ->paginate($request->integer('per_page', 15));

        return response()->json([
            'status'  => true,
            'message' => 'تم استرجاع رسائلك بنجاح',
            'data'    => $messages->map(fn($msg) => [
                'id'          => $msg->id,
                'subject'     => $msg->subject,
                'message'     => $msg->message,
                'status'      => $msg->status,
                'admin_reply' => $msg->admin_reply,
                'created_at'  => $msg->created_at,
            ]),
            'meta' => [
                'total'        => $messages->total(),
                'per_page'     => $messages->perPage(),
                'current_page' => $messages->currentPage(),
                'last_page'    => $messages->lastPage(),
            ],
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Admin: List all contact messages
    |--------------------------------------------------------------------------
    | GET /v1/admin/contact
    */
    public function index(Request $request)
    {
        $query = ContactMessage::with('user');

        // Filter by status if provided
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $messages = $query->orderByDesc('created_at')
            ->paginate($request->integer('per_page', 15));

        return response()->json([
            'status'  => true,
            'message' => 'تم استرجاع رسائل التواصل بنجاح',
            'data'    => $messages->map(fn($msg) => [
                'id'          => $msg->id,
                'subject'     => $msg->subject,
                'message'     => $msg->message,
                'status'      => $msg->status,
                'admin_reply' => $msg->admin_reply,
                'read_at'     => $msg->read_at,
                'created_at'  => $msg->created_at,
                'sender'      => [
                    'id'    => $msg->user?->id,
                    'name'  => $msg->user?->name,
                    'email' => $msg->user?->email,
                    'phone' => $msg->user?->phone,
                    'type'  => $msg->user?->type,
                ],
            ]),
            'meta' => [
                'total'        => $messages->total(),
                'per_page'     => $messages->perPage(),
                'current_page' => $messages->currentPage(),
                'last_page'    => $messages->lastPage(),
            ],
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Admin: Show a single contact message (marks it as read)
    |--------------------------------------------------------------------------
    | GET /v1/admin/contact/{message}
    */
    public function show(ContactMessage $contactMessage)
    {
        // Mark as read on first view
        if ($contactMessage->status === 'pending') {
            $contactMessage->update([
                'status'  => 'read',
                'read_at' => now(),
            ]);
        }

        return response()->json([
            'status'  => true,
            'message' => 'تم استرجاع الرسالة بنجاح',
            'data'    => [
                'id'          => $contactMessage->id,
                'subject'     => $contactMessage->subject,
                'message'     => $contactMessage->message,
                'status'      => $contactMessage->status,
                'admin_reply' => $contactMessage->admin_reply,
                'read_at'     => $contactMessage->read_at,
                'created_at'  => $contactMessage->created_at,
                'sender'      => [
                    'id'    => $contactMessage->user?->id,
                    'name'  => $contactMessage->user?->name,
                    'email' => $contactMessage->user?->email,
                    'phone' => $contactMessage->user?->phone,
                    'type'  => $contactMessage->user?->type,
                ],
            ],
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Admin: Reply to a contact message
    |--------------------------------------------------------------------------
    | POST /v1/admin/contact/{message}/reply
    */
    public function reply(Request $request, ContactMessage $contactMessage)
    {
        $request->validate([
            'reply' => ['required', 'string', 'min:3'],
        ], [
            'reply.required' => 'محتوى الرد مطلوب.',
            'reply.min'      => 'الرد يجب أن يكون 3 أحرف على الأقل.',
        ]);

        $contactMessage->update([
            'admin_reply' => $request->reply,
            'status'      => 'replied',
        ]);

        return response()->json([
            'status'  => true,
            'message' => 'تم إرسال الرد بنجاح',
            'data'    => [
                'id'          => $contactMessage->id,
                'subject'     => $contactMessage->subject,
                'admin_reply' => $contactMessage->admin_reply,
                'status'      => $contactMessage->status,
            ],
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Admin: Delete a contact message
    |--------------------------------------------------------------------------
    | DELETE /v1/admin/contact/{message}
    */
    public function destroy(ContactMessage $contactMessage)
    {
        $contactMessage->delete();

        return response()->json([
            'status'  => true,
            'message' => 'تم حذف الرسالة بنجاح',
        ]);
    }
}
