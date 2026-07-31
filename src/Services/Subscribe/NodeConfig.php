<?php

declare(strict_types=1);

namespace App\Services\Subscribe;

use function is_array;
use function is_bool;
use function is_numeric;
use function is_string;
use function strtolower;

/**
 * Helpers for parsing node custom_config used by XrayR / Hiddify-compatible clients.
 * Field names follow SSPanel-UIM + XrayR conventions (also compatible with Xboard-style Reality).
 */
final class NodeConfig
{
    public static function decode(?string $raw): array
    {
        if ($raw === null || $raw === '') {
            return [];
        }

        $decoded = json_decode($raw, true);

        return is_array($decoded) ? $decoded : [];
    }

    public static function isTruthy(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_numeric($value)) {
            return (int) $value === 1;
        }

        if (is_string($value)) {
            $v = strtolower(trim($value));

            return $v === '1' || $v === 'true' || $v === 'yes' || $v === 'on';
        }

        return false;
    }

    public static function port(array $config, int $default = 443): int
    {
        $port = $config['offset_port_user'] ?? $config['offset_port_node'] ?? $default;

        return (int) $port;
    }

    public static function isVless(array $config): bool
    {
        if (self::isTruthy($config['enable_vless'] ?? null)) {
            return true;
        }

        $protocol = strtolower((string) ($config['protocol'] ?? ''));

        return $protocol === 'vless';
    }

    public static function isReality(array $config): bool
    {
        if (self::isTruthy($config['enable_reality'] ?? null)) {
            return true;
        }

        $security = strtolower((string) ($config['security'] ?? ''));

        return $security === 'reality';
    }

    public static function security(array $config): string
    {
        if (self::isReality($config)) {
            return 'reality';
        }

        return strtolower((string) ($config['security'] ?? 'none'));
    }

    public static function flow(array $config): string
    {
        $flow = (string) ($config['flow'] ?? '');

        if ($flow !== '') {
            return $flow;
        }

        // Vision is the stable default for VLESS + REALITY / TLS on modern clients (Hiddify / Clash Meta).
        if (self::isVless($config) && (self::isReality($config) || self::security($config) === 'tls')) {
            $network = strtolower((string) ($config['network'] ?? 'tcp'));
            if ($network === '' || $network === 'tcp') {
                return 'xtls-rprx-vision';
            }
        }

        return '';
    }

    public static function host(array $config, string $fallback = ''): string
    {
        return (string) (
            $config['header']['request']['headers']['Host'][0]
            ?? $config['host']
            ?? $fallback
        );
    }

    public static function path(array $config, string $fallback = ''): string
    {
        return (string) (
            $config['header']['request']['path'][0]
            ?? $config['path']
            ?? $fallback
        );
    }

    public static function fingerprint(array $config): string
    {
        return (string) ($config['fingerprint'] ?? $config['client-fingerprint'] ?? 'chrome');
    }

    /**
     * Client-facing REALITY options (public key + short id + SNI).
     *
     * @return array{public_key: string, short_id: string, server_name: string, fingerprint: string}
     */
    public static function realityClient(array $config): array
    {
        $opts = is_array($config['reality-opts'] ?? null) ? $config['reality-opts'] : [];

        $publicKey = (string) (
            $opts['public_key']
            ?? $opts['public-key']
            ?? $config['public_key']
            ?? $config['public-key']
            ?? ''
        );

        $shortIds = $opts['short_ids'] ?? $opts['shortIds'] ?? $config['short_ids'] ?? [];
        $shortId = '';
        if (is_array($shortIds) && $shortIds !== []) {
            // Prefer first non-empty short id; empty string is a valid REALITY shortId but many clients prefer a set one.
            foreach ($shortIds as $id) {
                if ((string) $id !== '') {
                    $shortId = (string) $id;
                    break;
                }
            }
            if ($shortId === '') {
                $shortId = (string) $shortIds[0];
            }
        } else {
            $shortId = (string) ($opts['short_id'] ?? $opts['short-id'] ?? $config['short_id'] ?? '');
        }

        $serverNames = $opts['server_names'] ?? $opts['serverNames'] ?? [];
        $serverName = '';
        if (is_array($serverNames) && $serverNames !== []) {
            $serverName = (string) $serverNames[0];
        }
        if ($serverName === '') {
            $serverName = (string) (
                $opts['server_name']
                ?? $config['server_name']
                ?? $config['sni']
                ?? self::host($config)
            );
        }

        return [
            'public_key' => $publicKey,
            'short_id' => $shortId,
            'server_name' => $serverName,
            'fingerprint' => self::fingerprint($config),
        ];
    }

    public static function allowInsecure(array $config): bool
    {
        return self::isTruthy($config['allow_insecure'] ?? false);
    }
}
