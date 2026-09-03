<?php

declare(strict_types=1);

namespace App\Utils;

final class Cookie
{
    public static function set(array $arg, int $time): void
    {
        foreach ($arg as $key => $value) {
            setcookie($key, (string) $value, [
                'expires' => $time,
                'path' => '/',
                'secure' => self::isSecure(),
                'httponly' => true,
                'samesite' => 'Lax',
            ]);
        }
    }

    public static function setWithDomain(array $arg, int $time, string $domain): void
    {
        $domain = self::normalizeDomain($domain);
        $options = [
            'expires' => $time,
            'path' => '/',
            'secure' => self::isSecure(),
            'httponly' => true,
            'samesite' => 'Lax',
        ];

        // Invalid or IP hosts: omit Domain so cookie binds to current host
        if ($domain !== '') {
            $options['domain'] = $domain;
        }

        foreach ($arg as $key => $value) {
            setcookie($key, (string) $value, $options);
        }
    }

    public static function get(string $key): string
    {
        return $_COOKIE[$key] ?? '';
    }

    public static function isSecure(): bool
    {
        if (! empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
            return true;
        }

        $forwarded = strtolower((string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? ''));
        if ($forwarded === 'https') {
            return true;
        }

        return false;
    }

    /**
     * Strip port and reject IP literals so Set-Cookie Domain stays valid.
     */
    public static function normalizeDomain(string $host): string
    {
        $host = strtolower(trim($host));
        $host = preg_replace('/:\d+$/', '', $host) ?? $host;
        $host = rtrim($host, '.');

        if ($host === '' || $host === 'localhost') {
            return '';
        }

        if (filter_var($host, FILTER_VALIDATE_IP)) {
            return '';
        }

        return $host;
    }
}
