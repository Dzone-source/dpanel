<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Config;
use App\Utils\Tools;
use Psr\Http\Client\ClientExceptionInterface;
use RedisException;
use RuntimeException;
use Throwable;

final class Password
{
    /**
     * @throws ClientExceptionInterface
     * @throws RedisException
     * @throws RuntimeException
     * @throws Throwable
     */
    public static function sendResetEmail($email): void
    {
        $driver = (string) Config::obtain('email_driver');

        if ($driver === '' || $driver === 'none') {
            throw new RuntimeException(
                'Email driver chưa được cấu hình. Vui lòng cấu hình SMTP trong Admin → Cài đặt email.'
            );
        }

        $redis = (new Cache())->initRedis();
        $token = Tools::genRandomChar(64);
        $ttl = (int) Config::obtain('email_password_reset_ttl');

        if ($ttl <= 0) {
            $ttl = 3600;
        }

        $redis->setex('password_reset:' . $token, $ttl, $email);

        $subject = $_ENV['appName'] . '- Đặt lại mật khẩu';
        $resetUrl = $_ENV['baseUrl'] . '/password/token/' . $token;

        Mail::send(
            $email,
            $subject,
            'password_reset.tpl',
            [
                'resetUrl' => $resetUrl,
            ]
        );
    }
}
