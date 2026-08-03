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
            ?? $config['sni']
            ?? $config['host']
            ?? $config['server_name']
            ?? $fallback
        );
    }

    public static function network(array $config, string $default = 'tcp'): string
    {
        return (string) ($config['header']['type'] ?? $config['network'] ?? $default);
    }

    public static function serviceName(array $config): string
    {
        return (string) ($config['servicename'] ?? $config['service_name'] ?? $config['serviceName'] ?? '');
    }

    public static function publicKey(array $config): string
    {
        return self::realityClient($config)['public_key'];
    }

    public static function shortId(array $config): string
    {
        return self::realityClient($config)['short_id'];
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

    /**
     * Trojan password for SSPanel/DPanel: UUID first, then passwd (XrayR does the same).
     */
    public static function trojanPassword(object $user): string
    {
        $uuid = (string) ($user->uuid ?? '');
        if ($uuid !== '') {
            return $uuid;
        }

        return (string) ($user->passwd ?? '');
    }

    /**
     * SNI / servername for TLS clients. Prefer custom_config host, else node server hostname.
     */
    public static function sni(array $config, string $serverFallback = ''): string
    {
        $host = self::host($config);
        if ($host !== '') {
            return $host;
        }

        return $serverFallback;
    }

    /**
     * Trojan share-link query params aligned with HiddifyPanel xray.to_link / make_proxy.
     *
     * @see https://github.com/hiddify/HiddifyPanel hiddifypanel/hutils/proxy/xray.py
     *
     * @return array<string, string>
     */
    public static function trojanShareQuery(array $cfg, string $serverFallback = ''): array
    {
        $host = self::sni($cfg, $serverFallback);
        $network = strtolower((string) ($cfg['network'] ?? 'tcp'));
        if ($network === '') {
            $network = 'tcp';
        }

        $security = self::isReality($cfg) ? 'reality' : strtolower((string) ($cfg['security'] ?? 'tls'));
        if ($security === '') {
            $security = 'tls';
        }

        // ALPN: only emit when custom_config sets it, or for transports that need h2.
        // Clash Meta (working path) omits alpn for plain Trojan TCP — forcing
        // alpn=http/1.1 in share links makes Hiddify/sing-box restrict ClientHello
        // ALPN and is a leading cause of Hiddify-only mid-upload disconnects on
        // XrayR Trojan nodes where ClashMi works without alpn.
        $alpn = (string) ($cfg['alpn'] ?? '');
        if ($alpn === '' && ($network === 'grpc' || $network === 'h2')) {
            $alpn = 'h2';
        }

        // Minimal query for Hiddify/sing-box on XrayR Trojan TCP+TLS.
        // Do NOT force alpn / headerType — ClashMi works without them; forcing
        // them caused Hiddify-only upload drops. Keep hiddify=1 for app marker.
        $query = [
            'hiddify' => '1',
            'sni' => $host,
            'type' => $network,
            'fp' => self::fingerprint($cfg),
            'security' => $security,
        ];

        if ($alpn !== '') {
            $query['alpn'] = $alpn;
        }

        // headerType only when custom_config explicitly sets a non-none header.
        $headerType = (string) ($cfg['header']['type'] ?? $cfg['headerType'] ?? '');
        if ($headerType !== '' && $headerType !== 'none') {
            $query['headerType'] = $headerType;
        }

        // host is for WS/CDN; skip on plain TCP.
        if ($host !== '' && $network !== 'tcp') {
            $query['host'] = $host;
        }

        $path = self::path($cfg);
        if ($path !== '') {
            $query['path'] = $path;
        }

        $servicename = (string) ($cfg['servicename'] ?? $cfg['serviceName'] ?? '');
        if ($servicename !== '') {
            $query['serviceName'] = $servicename;
            if ($network === 'grpc') {
                $query['mode'] = (string) ($cfg['grpc_mode'] ?? 'gun');
            }
        }

        // HiddifyPanel only emits allowInsecure when true.
        if (self::allowInsecure($cfg)) {
            $query['allowInsecure'] = '1';
            $query['insecure'] = '1';
        }

        if (self::isTruthy($cfg['mux'] ?? false)) {
            $query['mux'] = '1';
        }

        if (self::isReality($cfg)) {
            $reality = self::realityClient($cfg);
            $query['security'] = 'reality';
            $query['pbk'] = $reality['public_key'];
            $query['sid'] = $reality['short_id'];
            $query['fp'] = $reality['fingerprint'];
            if ($reality['server_name'] !== '') {
                $query['sni'] = $reality['server_name'];
                $query['host'] = $reality['server_name'];
            }
        }

        return $query;
    }
}
