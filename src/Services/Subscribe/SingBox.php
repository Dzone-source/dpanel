<?php

declare(strict_types=1);

namespace App\Services\Subscribe;

use App\Services\Subscribe;
use App\Utils\Tools;
use function array_filter;
use function array_merge;
use function json_decode;
use function json_encode;

/**
 * Sing-box / Hiddify subscription.
 *
 * Aligned with Xboard: emit a Hiddify-friendly config (legacy DNS address
 * format, no remote rule-set downloads during import, clean trojan outbounds).
 */
final class SingBox extends Base
{
    public function getContent($user): string
    {
        $nodes = [];
        $node_names = [];
        $nodes_raw = Subscribe::getUserNodes($user);

        foreach ($nodes_raw as $node_raw) {
            $node_custom_config = json_decode((string) $node_raw->custom_config, true);
            if (! is_array($node_custom_config)) {
                $node_custom_config = [];
            }

            $node = match ((int) $node_raw->sort) {
                0 => $this->buildShadowsocks($user, $node_raw),
                1 => $this->buildShadowsocks2022($user, $node_raw, $node_custom_config),
                2 => $this->buildTuic($user, $node_raw, $node_custom_config),
                11 => $this->buildVmess($user, $node_raw, $node_custom_config),
                14 => $this->buildTrojan($user, $node_raw, $node_custom_config),
                default => [],
            };

            if ($node === []) {
                continue;
            }

            $nodes[] = $node;
            $node_names[] = $node_raw->name;
        }

        $config = $this->baseConfig($node_names);
        $config['outbounds'] = array_merge($config['outbounds'], $nodes);
        $config['experimental']['cache_file']['cache_id'] = (string) ($_ENV['appName'] ?? 'DPanel');

        return (string) json_encode($config, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    /**
     * Minimal Xboard-like template — no remote rule-set fetch on import.
     */
    private function baseConfig(array $node_names): array
    {
        return [
            'log' => [
                'disabled' => false,
                'level' => 'error',
                'timestamp' => true,
            ],
            'dns' => [
                'servers' => [
                    [
                        'tag' => 'remote',
                        'address' => 'tls://1.1.1.1',
                        'detour' => 'select',
                    ],
                    [
                        'tag' => 'local',
                        'address' => 'local',
                    ],
                ],
                'rules' => [
                    [
                        'outbound' => ['any'],
                        'server' => 'local',
                    ],
                    [
                        'clash_mode' => 'global',
                        'server' => 'remote',
                    ],
                    [
                        'clash_mode' => 'direct',
                        'server' => 'local',
                    ],
                ],
                'strategy' => 'prefer_ipv4',
            ],
            'inbounds' => [
                [
                    'type' => 'tun',
                    'tag' => 'tun-in',
                    'inet4_address' => '172.19.0.1/30',
                    'mtu' => 9000,
                    'auto_route' => true,
                    'strict_route' => true,
                    'stack' => 'system',
                    'sniff' => true,
                    'sniff_override_destination' => true,
                ],
                [
                    'type' => 'mixed',
                    'tag' => 'mixed-in',
                    'listen' => '127.0.0.1',
                    'listen_port' => 2080,
                    'sniff' => true,
                    'sniff_override_destination' => true,
                ],
            ],
            'outbounds' => [
                [
                    'tag' => 'select',
                    'type' => 'selector',
                    'outbounds' => array_merge(['auto'], $node_names),
                    'default' => 'auto',
                ],
                [
                    'tag' => 'auto',
                    'type' => 'urltest',
                    'outbounds' => $node_names,
                    'url' => 'https://www.gstatic.com/generate_204',
                    'interval' => '3m',
                    'tolerance' => 50,
                ],
                [
                    'tag' => 'direct',
                    'type' => 'direct',
                ],
                [
                    'tag' => 'block',
                    'type' => 'block',
                ],
                [
                    'tag' => 'dns-out',
                    'type' => 'dns',
                ],
            ],
            'route' => [
                'rules' => [
                    [
                        'protocol' => 'dns',
                        'outbound' => 'dns-out',
                    ],
                    [
                        'clash_mode' => 'Direct',
                        'outbound' => 'direct',
                    ],
                    [
                        'clash_mode' => 'Global',
                        'outbound' => 'select',
                    ],
                    [
                        'ip_is_private' => true,
                        'outbound' => 'direct',
                    ],
                ],
                'final' => 'select',
                'auto_detect_interface' => true,
            ],
            'experimental' => [
                'cache_file' => [
                    'enabled' => true,
                    'path' => 'cache.db',
                    'cache_id' => '',
                ],
            ],
        ];
    }

    private function buildShadowsocks($user, $node_raw): array
    {
        return [
            'type' => 'shadowsocks',
            'tag' => $node_raw->name,
            'server' => $node_raw->server,
            'server_port' => (int) $user->port,
            'method' => $user->method,
            'password' => $user->passwd,
        ];
    }

    private function buildShadowsocks2022($user, $node_raw, array $cfg): array
    {
        $port = $cfg['offset_port_user'] ?? ($cfg['offset_port_node'] ?? 443);
        $method = $cfg['method'] ?? '2022-blake3-aes-128-gcm';
        $user_pk = Tools::genSs2022UserPk($user->passwd, $method);
        if (! $user_pk) {
            return [];
        }

        $server_key = $cfg['server_key'] ?? '';
        $node = [
            'type' => 'shadowsocks',
            'tag' => $node_raw->name,
            'server' => $node_raw->server,
            'server_port' => (int) $port,
            'method' => $method,
            'password' => $server_key === '' ? $user_pk : $server_key . ':' . $user_pk,
        ];

        if (! empty($cfg['uot'])) {
            $node['udp_over_tcp'] = true;
        }

        return $node;
    }

    private function buildTuic($user, $node_raw, array $cfg): array
    {
        $port = $cfg['offset_port_user'] ?? ($cfg['offset_port_node'] ?? 443);
        $host = $cfg['host'] ?? '';
        $allow_insecure = filter_var($cfg['allow_insecure'] ?? false, FILTER_VALIDATE_BOOLEAN);

        $tls = array_filter([
            'enabled' => true,
            'server_name' => $host !== '' ? $host : null,
            'insecure' => $allow_insecure,
        ], static fn ($v) => $v !== null && $v !== '');

        return [
            'type' => 'tuic',
            'tag' => $node_raw->name,
            'server' => $node_raw->server,
            'server_port' => (int) $port,
            'uuid' => $user->uuid,
            'password' => $user->passwd,
            'congestion_control' => $cfg['congestion_control'] ?? 'bbr',
            'zero_rtt_handshake' => true,
            'tls' => $tls,
        ];
    }

    private function buildVmess($user, $node_raw, array $cfg): array
    {
        $port = $cfg['offset_port_user'] ?? ($cfg['offset_port_node'] ?? 443);
        $network = $cfg['network'] ?? '';
        $transport_type = $network === 'tcp' ? '' : $network;
        $host = $cfg['header']['request']['headers']['Host'][0] ?? $cfg['host'] ?? '';
        $path = $cfg['header']['request']['path'][0] ?? $cfg['path'] ?? '';
        $headers = $cfg['header']['request']['headers'] ?? [];
        $service_name = $cfg['servicename'] ?? '';
        $utls = filter_var($cfg['utls'] ?? false, FILTER_VALIDATE_BOOLEAN);

        $node = [
            'type' => 'vmess',
            'tag' => $node_raw->name,
            'server' => $node_raw->server,
            'server_port' => (int) $port,
            'uuid' => $user->uuid,
            'security' => 'auto',
            'alter_id' => 0,
            'tls' => array_filter([
                'enabled' => true,
                'server_name' => $host !== '' ? $host : null,
                'utls' => $utls ? [
                    'enabled' => true,
                    'fingerprint' => 'chrome',
                ] : null,
            ], static fn ($v) => $v !== null),
            'packet_encoding' => 'xudp',
        ];

        $transport = array_filter([
            'type' => $transport_type !== '' ? $transport_type : null,
            'path' => $path !== '' ? $path : null,
            'headers' => $headers !== [] ? $headers : null,
            'service_name' => $service_name !== '' ? $service_name : null,
        ], static fn ($v) => $v !== null && $v !== '');

        if ($transport !== []) {
            $node['transport'] = $transport;
        }

        return $node;
    }

    private function buildTrojan($user, $node_raw, array $cfg): array
    {
        $port = $cfg['offset_port_user'] ?? ($cfg['offset_port_node'] ?? 443);
        $host = $cfg['host'] ?? '';
        $allow_insecure = filter_var($cfg['allow_insecure'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $network = $cfg['network'] ?? '';
        $path = $cfg['header']['request']['path'][0] ?? $cfg['path'] ?? '';
        $headers = $cfg['header']['request']['headers'] ?? [];
        $service_name = $cfg['servicename'] ?? '';

        $node = [
            'type' => 'trojan',
            'tag' => $node_raw->name,
            'server' => $node_raw->server,
            'server_port' => (int) $port,
            'password' => $user->uuid,
            'tls' => array_filter([
                'enabled' => true,
                'server_name' => $host !== '' ? $host : null,
                'insecure' => $allow_insecure,
            ], static fn ($v) => $v !== null && $v !== ''),
        ];

        // Xboard omits transport for plain TCP — empty transport breaks Hiddify.
        if ($network !== '' && $network !== 'tcp') {
            $transport = array_filter([
                'type' => $network,
                'path' => $path !== '' ? $path : null,
                'headers' => $headers !== [] ? $headers : null,
                'service_name' => $service_name !== '' ? $service_name : null,
            ], static fn ($v) => $v !== null && $v !== '');
            if ($transport !== []) {
                $node['transport'] = $transport;
            }
        }

        return $node;
    }
}
