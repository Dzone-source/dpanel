<?php

declare(strict_types=1);

namespace App\Services\Subscribe;

/**
 * Helpers for reading SSPanel-style node custom_config fields.
 * Field names keep DPanel/SSPanel flat JSON; Reality/Hysteria2 map inspired by Xboard.
 */
final class NodeConfigHelper
{
    public static function port(array $config, int $default = 443): int
    {
        return (int) ($config['offset_port_user'] ?? $config['offset_port_node'] ?? $default);
    }

    public static function host(array $config, string $fallback = ''): string
    {
        return (string) ($config['sni']
            ?? $config['host']
            ?? $config['server_name']
            ?? $config['header']['request']['headers']['Host'][0]
            ?? $fallback);
    }

    public static function path(array $config): string
    {
        return (string) ($config['header']['request']['path'][0] ?? $config['path'] ?? '');
    }

    public static function network(array $config, string $default = 'tcp'): string
    {
        return (string) ($config['header']['type'] ?? $config['network'] ?? $default);
    }

    public static function allowInsecure(array $config): bool
    {
        return (bool) ($config['allow_insecure'] ?? false);
    }

    public static function security(array $config): string
    {
        $security = strtolower((string) ($config['security'] ?? 'none'));

        if ($security === '' || $security === 'auto') {
            return 'none';
        }

        // Accept Xboard-style numeric tls: 1=tls, 2=reality
        if ($security === '1') {
            return 'tls';
        }
        if ($security === '2') {
            return 'reality';
        }

        return $security;
    }

    public static function fingerprint(array $config): string
    {
        return (string) ($config['fingerprint'] ?? $config['fp'] ?? 'chrome');
    }

    public static function publicKey(array $config): string
    {
        return (string) ($config['public_key'] ?? $config['pbk'] ?? $config['reality_settings']['public_key'] ?? '');
    }

    public static function shortId(array $config): string
    {
        return (string) ($config['short_id'] ?? $config['sid'] ?? $config['reality_settings']['short_id'] ?? '');
    }

    public static function flow(array $config): string
    {
        return (string) ($config['flow'] ?? '');
    }

    public static function serviceName(array $config): string
    {
        return (string) ($config['servicename'] ?? $config['service_name'] ?? $config['serviceName'] ?? '');
    }
}
