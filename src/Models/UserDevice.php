<?php

declare(strict_types=1);

namespace App\Models;

final class UserDevice extends Model
{
    protected $table = 'user_device';
    protected $hidden = ['token', 'uuid', 'password'];
    protected $casts = ['id' => 'int', 'user_id' => 'int', 'revoked_at' => 'int', 'last_seen_at' => 'int'];

    public function subscriptionUrl(): string
    {
        return rtrim((string) $_ENV['subUrl'], '/') . '/device-sub/' . $this->token;
    }
}
