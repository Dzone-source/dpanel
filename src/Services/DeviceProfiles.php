<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use App\Models\UserDevice;
use DomainException;
use Ramsey\Uuid\Uuid;

final class DeviceProfiles
{
    public static function csrf(User $user): string
    {
        return hash_hmac('sha256', 'device-profiles:' . $user->id . ':' . $user->pass, (string) $_ENV['key']);
    }

    public static function create(int $userId, string $name): UserDevice
    {
        $name = trim($name);
        if ($name === '' || mb_strlen($name) > 100) {
            throw new DomainException('Tên thiết bị cần từ 1 đến 100 ký tự.');
        }
        // Lock the account so concurrent requests cannot bypass its registration limit.
        return DB::connection()->transaction(static function () use ($userId, $name): UserDevice {
            $user = User::where('id', $userId)->lockForUpdate()->firstOrFail();
            if (! self::accountAllowed($user)) {
                throw new DomainException('Tài khoản bị khóa hoặc gói dịch vụ đã hết hạn.');
            }
            if (! $user->device_profiles_enabled) {
                throw new DomainException('Hãy bật chế độ profile thiết bị trước.');
            }
            $limit = (int) $user->device_limit;
            if ($limit > 0 && UserDevice::where('user_id', $userId)->whereNull('revoked_at')->count() >= $limit) {
                throw new DomainException('Đã đủ số profile được cấp. Hãy thu hồi một profile cũ.');
            }
            return UserDevice::create([
                'user_id' => $userId, 'name' => $name,
                'uuid' => Uuid::uuid4()->toString(), 'password' => bin2hex(random_bytes(32)),
                'token' => bin2hex(random_bytes(32)), 'created_at' => time(),
            ]);
        });
    }

    public static function accountAllowed(User $user): bool
    {
        return (int) $user->is_banned === 0 && strtotime((string) $user->class_expire) > time();
    }

    public static function subscription(string $token): ?User
    {
        if (! preg_match('/^[a-f0-9]{64}$/D', $token)) {
            return null;
        }
        $device = UserDevice::where('token', $token)->whereNull('revoked_at')->first();
        $owner = $device === null ? null : User::find($device->user_id);
        if ($owner === null || ! $owner->device_profiles_enabled || ! self::accountAllowed($owner)) {
            return null;
        }
        $profile = clone $owner;
        $profile->uuid = $device->uuid;
        $profile->passwd = $device->password;
        $profile->device_subscription_url = $device->subscriptionUrl();
        return $profile;
    }

    /** Expand an eligible account into independently revocable node credentials. */
    public static function nodeCredentials(User $user, bool $capable, int $nodeSort, ?iterable $devices = null): array
    {
        if (! $user->device_profiles_enabled) {
            return [$user];
        }
        if (! $capable || ! in_array($nodeSort, [11, 12, 14], true)) {
            return [];
        }
        $result = [];
        foreach ($devices ?? UserDevice::where('user_id', $user->id)->whereNull('revoked_at')->get() as $device) {
            $credential = clone $user;
            $credential->uuid = $device->uuid;
            $credential->passwd = $device->password;
            $credential->device_id = $device->id;
            // One credential can roam between IPv4/IPv6; this is not an IP limit.
            $credential->node_iplimit = 0;
            $result[] = $credential;
        }
        return $result;
    }

    public static function revoke(int $userId, int $deviceId): bool
    {
        return UserDevice::where('user_id', $userId)->where('id', $deviceId)
            ->whereNull('revoked_at')->update(['revoked_at' => time()]) > 0;
    }
}
