<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Message;
use App\Enums\UserTypeEnum;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MessageTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test allowed user can upload a file in the chat.
     */
    public function test_user_can_send_file_attachment()
    {
        Storage::fake('public');

        $resident = User::factory()->create(['type' => UserTypeEnum::RESIDENT]);
        $provider = User::factory()->create(['type' => UserTypeEnum::PROVIDER]);

        Sanctum::actingAs($resident);

        $file = UploadedFile::fake()->create('contract.pdf', 500, 'application/pdf');

        $payload = [
            'receiver_id' => $provider->id,
            'file'        => $file,
        ];

        $response = $this->postJson('/api/v1/messages', $payload);

        $response->assertStatus(201)
            ->assertJson([
                'status'  => true,
                'message' => 'تم إرسال الرسالة بنجاح',
            ])
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'file_path',
                    'file_name',
                    'file_type',
                    'file_url',
                ]
            ]);

        $message = Message::first();
        $this->assertNotNull($message->file_path);
        $this->assertEquals('contract.pdf', $message->file_name);
        $this->assertEquals('application/pdf', $message->file_type);

        Storage::disk('public')->assertExists($message->file_path);
    }

    /**
     * Test allowed user (resident) can send a message to a provider.
     */
    public function test_user_can_send_message_to_valid_receiver()
    {
        $resident = User::factory()->create(['type' => UserTypeEnum::RESIDENT]);
        $provider = User::factory()->create(['type' => UserTypeEnum::PROVIDER]);

        Sanctum::actingAs($resident);

        $payload = [
            'receiver_id' => $provider->id,
            'message'     => 'مرحبا، هل السكن متوفر؟',
        ];

        $response = $this->postJson('/api/v1/messages', $payload);

        $response->assertStatus(201)
            ->assertJson([
                'status'  => true,
                'message' => 'تم إرسال الرسالة بنجاح',
            ])
            ->assertJsonStructure([
                'status',
                'message',
                'data' => [
                    'id',
                    'message',
                    'sender_id',
                    'receiver_id',
                    'sender' => ['id', 'name', 'type'],
                    'receiver' => ['id', 'name', 'type'],
                    'read_at',
                    'created_at',
                ]
            ]);

        $this->assertDatabaseHas('messages', [
            'sender_id'   => $resident->id,
            'receiver_id' => $provider->id,
            'message'     => 'مرحبا، هل السكن متوفر؟',
        ]);
    }

    /**
     * Test user cannot send a message to themselves.
     */
    public function test_user_cannot_send_message_to_themselves()
    {
        $resident = User::factory()->create(['type' => UserTypeEnum::RESIDENT]);

        Sanctum::actingAs($resident);

        $payload = [
            'receiver_id' => $resident->id,
            'message'     => 'رسالة لنفسي',
        ];

        $response = $this->postJson('/api/v1/messages', $payload);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['receiver_id']);
    }

    /**
     * Test property_owner cannot send or receive messages.
     */
    public function test_property_owner_cannot_send_or_receive_messages()
    {
        $owner = User::factory()->create(['type' => UserTypeEnum::PROPERTY_OWNER]);
        $provider = User::factory()->create(['type' => UserTypeEnum::PROVIDER]);

        // 1. Property owner tries to send a message
        Sanctum::actingAs($owner);

        $responseSend = $this->postJson('/api/v1/messages', [
            'receiver_id' => $provider->id,
            'message'     => 'مرحبا',
        ]);

        $responseSend->assertStatus(403);

        // 2. Provider tries to send a message to property owner
        Sanctum::actingAs($provider);

        $responseSendToOwner = $this->postJson('/api/v1/messages', [
            'receiver_id' => $owner->id,
            'message'     => 'مرحبا مالك السكن',
        ]);

        $responseSendToOwner->assertStatus(422)
            ->assertJsonValidationErrors(['receiver_id']);
    }

    /**
     * Test users can list their conversation chats.
     */
    public function test_user_can_list_own_conversations()
    {
        $resident = User::factory()->create(['type' => UserTypeEnum::RESIDENT]);
        $provider1 = User::factory()->create(['type' => UserTypeEnum::PROVIDER]);
        $provider2 = User::factory()->create(['type' => UserTypeEnum::PROVIDER]);

        // Send messages to create chat histories
        Message::create([
            'sender_id'   => $resident->id,
            'receiver_id' => $provider1->id,
            'message'     => 'الرسالة الأولى لمزود 1',
        ]);

        Message::create([
            'sender_id'   => $provider2->id,
            'receiver_id' => $resident->id,
            'message'     => 'الرسالة الأولى من مزود 2',
        ]);

        Sanctum::actingAs($resident);

        $response = $this->getJson('/api/v1/messages/chats');

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data')
            ->assertJsonStructure([
                'status',
                'message',
                'data' => [
                    '*' => [
                        'partner' => ['id', 'name', 'email', 'type'],
                        'last_message' => ['id', 'message', 'sender_id', 'created_at', 'read_at'],
                        'unread_count',
                    ]
                ]
            ]);
    }

    /**
     * Test user can retrieve chat history and unread messages are marked as read.
     */
    public function test_user_can_view_chat_history_and_marks_as_read()
    {
        $resident = User::factory()->create(['type' => UserTypeEnum::RESIDENT]);
        $provider = User::factory()->create(['type' => UserTypeEnum::PROVIDER]);

        // Unread message from provider to resident
        $unreadMsg = Message::create([
            'sender_id'   => $provider->id,
            'receiver_id' => $resident->id,
            'message'     => 'هل لديك سؤال؟',
            'read_at'     => null,
        ]);

        Sanctum::actingAs($resident);

        $response = $this->getJson("/api/v1/messages/user/{$provider->id}");

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $unreadMsg->id);

        // Assert message is marked as read in database
        $this->assertNotNull($unreadMsg->fresh()->read_at);
    }

    /**
     * Test admin can view and delete messages.
     */
    public function test_admin_can_manage_messages()
    {
        $admin = User::factory()->create(['type' => UserTypeEnum::ADMIN]);
        $resident = User::factory()->create(['type' => UserTypeEnum::RESIDENT]);
        $provider = User::factory()->create(['type' => UserTypeEnum::PROVIDER]);

        $message = Message::create([
            'sender_id'   => $resident->id,
            'receiver_id' => $provider->id,
            'message'     => 'رسالة للوسط الإداري',
        ]);

        Sanctum::actingAs($admin);

        // List all messages in the system
        $responseList = $this->getJson('/api/v1/admin/messages');
        $responseList->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'message',
                'data' => [
                    '*' => [
                        'id',
                        'message',
                        'sender',
                        'receiver',
                    ]
                ]
            ]);

        // Delete message
        $responseDelete = $this->deleteJson("/api/v1/admin/messages/{$message->id}");
        $responseDelete->assertStatus(200)
            ->assertJson([
                'status'  => true,
                'message' => 'تم حذف الرسالة بنجاح',
            ]);

        $this->assertDatabaseMissing('messages', [
            'id' => $message->id,
        ]);
    }
}
