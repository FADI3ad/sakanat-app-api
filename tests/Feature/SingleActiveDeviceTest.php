<?php

namespace Tests\Feature;

use App\Enums\UserTypeEnum;
use App\Models\User;
use App\Models\UserActiveDevice;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SingleActiveDeviceTest extends TestCase
{
    use RefreshDatabase;

    public function test_first_login_creates_one_active_device(): void
    {
        $user = User::factory()->create(['password' => bcrypt('password')]);

        $response = $this->postJson('/api/v1/auth/login', $this->credentials($user));

        $response->assertOk()->assertJsonPath('success', true);
        $this->assertDatabaseHas('user_active_devices', [
            'user_id' => $user->id,
            'revoked_at' => null,
        ]);
    }

    public function test_second_login_is_rejected_without_replacing_first_device(): void
    {
        $user = User::factory()->create(['password' => bcrypt('password')]);
        $first = $this->postJson('/api/v1/auth/login', $this->credentials($user));

        $second = $this->postJson('/api/v1/auth/login', $this->credentials($user));

        $second->assertStatus(409)->assertJsonPath('code', 'ACCOUNT_ALREADY_ACTIVE');
        $this->withHeaders([
            'Authorization' => 'Bearer '.$first->json('data.token'),
        ])->getJson('/api/v1/contact/my')->assertOk();
        $this->assertDatabaseCount('user_active_devices', 1);
    }

    public function test_admin_can_revoke_and_a_new_device_can_login(): void
    {
        $user = User::factory()->create(['password' => bcrypt('password')]);
        $admin = User::factory()->create([
            'type' => UserTypeEnum::ADMIN->value,
            'password' => bcrypt('password'),
        ]);
        $first = $this->postJson('/api/v1/auth/login', $this->credentials($user));
        $adminLogin = $this->postJson('/api/v1/auth/login', $this->credentials($admin));

        $this->postJson("/api/v1/admin/users/{$user->id}/revoke-device", [], [
            'Authorization' => 'Bearer '.$adminLogin->json('data.token'),
        ])->assertOk();

        $this->assertDatabaseHas('user_active_devices', [
            'user_id' => $user->id,
        ]);
        $this->assertNotNull($user->activeDevice()->first()->revoked_at);

        $this->app['auth']->forgetGuards();
        $revokedResponse = $this->withHeaders([
            'Authorization' => 'Bearer '.$first->json('data.token'),
        ])->getJson('/api/v1/contact/my');
        $revokedResponse->assertStatus(401)->assertJsonPath('code', 'DEVICE_REVOKED');

        $this->postJson('/api/v1/auth/login', $this->credentials($user))->assertOk();
    }

    public function test_logout_releases_the_active_device(): void
    {
        $user = User::factory()->create(['password' => bcrypt('password')]);
        $first = $this->postJson('/api/v1/auth/login', $this->credentials($user));

        $this->postJson('/api/v1/auth/logout', [], [
            'Authorization' => 'Bearer '.$first->json('data.token'),
        ])->assertOk();

        $this->postJson('/api/v1/auth/login', $this->credentials($user))->assertOk();
    }

    public function test_database_rejects_a_second_device_row_for_the_same_user(): void
    {
        $user = User::factory()->create(['password' => bcrypt('password')]);
        $this->postJson('/api/v1/auth/login', $this->credentials($user))->assertOk();

        $this->expectException(QueryException::class);
        UserActiveDevice::create([
            'user_id' => $user->id,
            'device_identifier' => str_repeat('a', 64),
            'created_at' => now(),
            'last_activity_at' => now(),
        ]);
    }

    private function credentials(User $user): array
    {
        return ['email' => $user->email, 'password' => 'password'];
    }
}
