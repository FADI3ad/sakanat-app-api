<?php

namespace App\Services;

use App\Exceptions\AccountAlreadyActiveException;
use App\Models\User;
use App\Models\UserActiveDevice;
use Illuminate\Support\Facades\DB;

class ActiveDeviceService
{
    public function acquire(User $user): array
    {
        return DB::transaction(function () use ($user): array {
            $lockedUser = User::query()->whereKey($user->id)->lockForUpdate()->firstOrFail();
            $activeDevice = UserActiveDevice::query()
                ->where('user_id', $lockedUser->id)
                ->lockForUpdate()
                ->first();

            if ($activeDevice && $activeDevice->revoked_at === null) {
                throw new AccountAlreadyActiveException;
            }

            $newToken = $lockedUser->createToken('auth_token');
            $now = now();

            UserActiveDevice::query()->updateOrCreate(
                ['user_id' => $lockedUser->id],
                [
                    'personal_access_token_id' => $newToken->accessToken->id,
                    'device_identifier' => hash('sha256', bin2hex(random_bytes(32))),
                    'created_at' => $now,
                    'last_activity_at' => $now,
                    'revoked_at' => null,
                ],
            );

            return ['user' => $lockedUser, 'token' => $newToken->plainTextToken];
        });
    }

    public function release(User $user, ?int $tokenId): void
    {
        DB::transaction(function () use ($user, $tokenId): void {
            $activeDevice = UserActiveDevice::query()
                ->where('user_id', $user->id)
                ->when($tokenId, fn ($query) => $query->where('personal_access_token_id', $tokenId))
                ->lockForUpdate()
                ->first();

            $activeDevice?->delete();
        });
    }

    public function revoke(User $user): bool
    {
        return DB::transaction(function () use ($user): bool {
            User::query()->whereKey($user->id)->lockForUpdate()->firstOrFail();

            return UserActiveDevice::query()
                ->where('user_id', $user->id)
                ->whereNull('revoked_at')
                ->update(['revoked_at' => now()]) > 0;
        });
    }
}
