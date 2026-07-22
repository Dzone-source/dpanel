<?php

declare(strict_types=1);

namespace App\Services\Subscribe;

use App\Services\Subscribe;
use App\Utils\Tools;
use function array_filter;
use function array_merge;
use function array_unique;
use function array_values;
use function filter_var;
use function is_string;
use function json_decode;
use function json_encode;
use const FILTER_VALIDATE_BOOLEAN;
use const FILTER_VALIDATE_IP;

/**
 * Sing-box / Hiddify subscription.
 *
 * Layout mirrors Hiddify-Manager / Hiddify-Panel:
 * - base_singbox_config.json.j2 (DNS, TUN, route, experimental)
 * - hutils/proxy/singbox.py (outbounds / Trojan TLS)
 *
 * SoftBank tweak: TUN MTU 1400 (Hiddify default 9000 can black-hole on JP mobile).
 */
final class SingBox extends Base
{
    public function getContent($user): string
    {
        $nodes = [];
        $node_names = [];
        $dns_direct_domains = [
            'github.com',
            'githubusercontent.com',
            'raw.githubusercontent.com',
            '1.1.1.1',
            '8.8.8.8',
        ];
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

            $server = (string) ($node['server'] ?? $node_raw->server);
            if ($server !== '' && filter_var($server, FILTER_VALIDATE_IP) === false) {
                $dns_direct_domains[] = $server;
            }
            $sni = (string) ($node_custom_config['host'] ?? '');
            if ($sni !== '' && filter_var($sni, FILTER_VALIDATE_IP) === false) {
                $dns_direct_domains[] = $sni;
            }
        }

        $dns_direct_domains = array_values(array_unique(array_filter(
            $dns_direct_domains,
            static fn ($d): bool => is_string($d) && $d !== ''
        )));

        $config = $this->baseConfig($node_names, $dns_direct_domains);
        $config['outbounds'] = array_merge($config['outbounds'], $nodes);
        $config['experimental']['cache_file']['cache_id'] = (string) ($user->uuid ?? ($_ENV['appName'] ?? 'DPanel'));

        return (string) json_encode($config, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    /**
     * Hiddify-Panel base_singbox_config.json.j2 (modern sing-box / Hiddify Next).
     */
    private function baseConfig(array $node_names, array $dns_direct_domains): array
    {
        $selector_outbounds = $node_names === [] ? ['direct'] : array_merge(['auto'], $node_names);
        $auto_outbounds = $node_names === [] ? ['direct'] : $node_names;

        return [
            'log' => [
                'disabled' => false,
                'level' => 'warn',
                'timestamp' => true,
            ],
            'dns' => [
                'servers' => [
                    // Remote DNS via proxy (Hiddify); resolver itself is direct.
                    [
                        'tag' => 'dns-remote',
                        'address' => 'tcp://1.1.1.1',
                        'address_resolver' => 'dns-local',
                        'strategy' => 'prefer_ipv4',
                        'detour' => 'select',
                    ],
                    // Bootstrap / node hostname resolution — always direct (avoids Connecting hang).
                    [
                        'tag' => 'dns-local',
                        'address' => '8.8.8.8',
                        'detour' => 'direct',
                    ],
                    [
                        'tag' => 'dns-block',
                        'address' => 'rcode://success',
                    ],
                ],
                'rules' => [
                    [
                        'domain' => $dns_direct_domains,
                        'server' => 'dns-local',
                    ],
                    [
                        'outbound' => 'direct',
                        'server' => 'dns-local',
                    ],
                ],
                // Hiddify final = dns-local so TUN does not chicken-egg on SoftBank DNS.
                'final' => 'dns-local',
                'reverse_mapping' => true,
                'strategy' => 'prefer_ipv4',
                'independent_cache' => true,
            ],
            'inbounds' => [
                [
                    'type' => 'direct',
                    'tag' => 'dns-in',
                    'listen' => '127.0.0.1',
                    'listen_port' => 6450,
                    'override_address' => '8.8.8.8',
                    'override_port' => 53,
                ],
                [
                    'type' => 'tun',
                    'tag' => 'tun-in',
                    'interface_name' => 'tun0',
                    'inet4_address' => '172.19.0.1/30',
                    // SoftBank/Linemo: Hiddify uses 9000; high MTU often black-holes on JP 4G/5G.
                    'mtu' => 1400,
                    'auto_route' => true,
                    'strict_route' => true,
                    'stack' => 'system',
                    'sniff' => true,
                    'sniff_override_destination' => false,
                    'endpoint_independent_nat' => true,
                    'domain_strategy' => 'prefer_ipv4',
                ],
                [
                    'type' => 'mixed',
                    'tag' => 'mixed-in',
                    'listen' => '127.0.0.1',
                    'listen_port' => 2334,
                    'domain_strategy' => 'prefer_ipv4',
                    'sniff' => true,
                    'sniff_override_destination' => false,
                ],
            ],
            'outbounds' => [
                [
                    'tag' => 'select',
                    'type' => 'selector',
                    'outbounds' => $selector_outbounds,
                    'default' => $node_names === [] ? 'direct' : 'auto',
                    'interrupt_exist_connections' => false,
                ],
                [
                    'tag' => 'auto',
                    'type' => 'urltest',
                    'outbounds' => $auto_outbounds,
                    'url' => 'https://www.gstatic.com/generate_204',
                    'interval' => '10m',
                    'tolerance' => 200,
                ],
                [
                    'tag' => 'direct',
                    'type' => 'direct',
                ],
                [
                    'tag' => 'bypass',
                    'type' => 'direct',
                ],
                [
                    'tag' => 'block',
                    'type' => 'block',
                ],
            ],
            'route' => [
                'auto_detect_interface' => true,
                'override_android_vpn' => true,
                'final' => 'select',
                'rules' => [
                    [
                        'inbound' => ['tun-in', 'mixed-in'],
                        'port' => [53],
                        'action' => 'hijack-dns',
                    ],
                    [
                        'inbound' => ['dns-in'],
                        'action' => 'hijack-dns',
                    ],
                    [
                        'inbound' => ['tun-in', 'mixed-in'],
                        'action' => 'sniff',
                        'timeout' => '1s',
                    ],
                    [
                        'protocol' => 'quic',
                        'port' => [443],
                        'action' => 'reject',
                    ],
                    [
                        'ip_cidr' => ['224.0.0.0/3', 'ff00::/8'],
                        'source_ip_cidr' => ['224.0.0.0/3', 'ff00::/8'],
                        'outbound' => 'block',
                    ],
                    [
                        'ip_is_private' => true,
                        'outbound' => 'direct',
                    ],
                ],
            ],
            'experimental' => [
                'clash_api' => [
                    'external_controller' => '127.0.0.1:9090',
                ],
                'cache_file' => [
                    'enabled' => true,
                    'path' => 'cache.db',
                    'cache_id' => '',
                    'store_fakeip' => true,
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
            $node['udp_over_tcp'] = [
                'enabled' => true,
                'version' => 2,
            ];
        }

        return $node;
    }

    private function buildTuic($user, $node_raw, array $cfg): array
    {
        $port = $cfg['offset_port_user'] ?? ($cfg['offset_port_node'] ?? 443);
        $host = $cfg['host'] ?? '';
        $allow_insecure = filter_var($cfg['allow_insecure'] ?? false, FILTER_VALIDATE_BOOLEAN);

        if (! $allow_insecure && $host !== '' && strcasecmp($host, (string) $node_raw->server) !== 0) {
            $allow_insecure = true;
        }

        $tls = array_filter([
            'enabled' => true,
            'server_name' => $host !== '' ? $host : null,
            'insecure' => $allow_insecure,
            'alpn' => ['h3'],
        ], static fn ($v) => $v !== null && $v !== '');

        return [
            'type' => 'tuic',
            'tag' => $node_raw->name,
            'server' => $node_raw->server,
            'server_port' => (int) $port,
            'uuid' => $user->uuid,
            'password' => $user->passwd,
            'congestion_control' => $cfg['congestion_control'] ?? 'cubic',
            'udp_relay_mode' => 'native',
            'zero_rtt_handshake' => filter_var($cfg['zero_rtt_handshake'] ?? false, FILTER_VALIDATE_BOOLEAN),
            'heartbeat' => '10s',
            'tls' => $tls,
        ];
    }

    private function buildVmess($user, $node_raw, array $cfg): array
    {
        $port = $cfg['offset_port_user'] ?? ($cfg['offset_port_node'] ?? 443);
        $network = (string) ($cfg['network'] ?? '');
        if ($network === '' || $network === 'none') {
            $network = 'tcp';
        }
        $host = $cfg['header']['request']['headers']['Host'][0] ?? $cfg['host'] ?? '';
        $path = $cfg['header']['request']['path'][0] ?? $cfg['path'] ?? '';
        $headers = $cfg['header']['request']['headers'] ?? [];
        $service_name = $cfg['servicename'] ?? '';
        $utls = filter_var($cfg['utls'] ?? true, FILTER_VALIDATE_BOOLEAN);
        $security = (string) ($cfg['security'] ?? 'none');
        $tls_enabled = $security === 'tls' || $security === 'auto';
        $allow_insecure = filter_var($cfg['allow_insecure'] ?? false, FILTER_VALIDATE_BOOLEAN);
        if ($tls_enabled && ! $allow_insecure && $host !== '' &&
            strcasecmp((string) $host, (string) $node_raw->server) !== 0
        ) {
            $allow_insecure = true;
        }

        $node = [
            'type' => 'vmess',
            'tag' => $node_raw->name,
            'server' => $node_raw->server,
            'server_port' => (int) $port,
            'uuid' => $user->uuid,
            'security' => 'auto',
            'alter_id' => 0,
            'packet_encoding' => 'xudp',
        ];

        if ($tls_enabled) {
            $node['tls'] = array_filter([
                'enabled' => true,
                'server_name' => $host !== '' ? $host : null,
                'insecure' => $allow_insecure,
                'utls' => $utls ? [
                    'enabled' => true,
                    'fingerprint' => 'chrome',
                ] : null,
            ], static fn ($v) => $v !== null);
        }

        $transport = $this->buildTransport($network, $path, $headers, $service_name, $host);
        if ($transport !== []) {
            $node['transport'] = $transport;
        }

        return $node;
    }

    /**
     * Hiddify singbox.py add_tls + add_transport for Trojan.
     * Plain TCP (XrayR SoftBank): no transport object; ALPN http/1.1 (Hiddify default).
     */
    private function buildTrojan($user, $node_raw, array $cfg): array
    {
        $port = $cfg['offset_port_user'] ?? ($cfg['offset_port_node'] ?? 443);
        $host = (string) ($cfg['host'] ?? '');
        $network = (string) ($cfg['network'] ?? '');
        if ($network === '' || $network === 'none') {
            $network = 'tcp';
        }
        $path = $cfg['header']['request']['path'][0] ?? $cfg['path'] ?? '';
        $headers = $cfg['header']['request']['headers'] ?? [];
        $service_name = (string) ($cfg['servicename'] ?? '');

        // SoftBank unlock / fake-SNI — same as Hiddify Fake mode (insecure=true).
        $allow_insecure = true;

        // Hiddify shared.py: tcp → http/1.1; grpc/h2 → h2
        $alpn = match (true) {
            $network === 'grpc', $network === 'h2' => ['h2'],
            default => ['http/1.1'],
        };
        if (isset($cfg['alpn']) && is_string($cfg['alpn']) && $cfg['alpn'] !== '') {
            $alpn = array_values(array_filter(array_map('trim', explode(',', $cfg['alpn']))));
        }

        $tls = [
            'enabled' => true,
            'insecure' => $allow_insecure,
            'alpn' => $alpn,
            'utls' => [
                'enabled' => true,
                'fingerprint' => 'chrome',
            ],
        ];
        if ($host !== '') {
            $tls['server_name'] = $host;
        }

        $node = [
            'type' => 'trojan',
            'tag' => $node_raw->name,
            'server' => $node_raw->server,
            'server_port' => (int) $port,
            'password' => $user->uuid,
            'tls' => $tls,
        ];

        // Plain TCP: omit transport (XrayR). WS/gRPC/httpupgrade only.
        // Do NOT map tcp→http like Hiddify server HTTP camouflage — that breaks SoftBank Trojan.
        if ($network !== 'tcp') {
            $transport = $this->buildTransport($network, $path, $headers, $service_name, $host);
            if ($transport !== []) {
                $node['transport'] = $transport;
            }
        }

        return $node;
    }

    /**
     * Subset of Hiddify singbox.py add_transport (ws / httpupgrade / grpc).
     */
    private function buildTransport(
        string $network,
        string|array $path,
        array $headers,
        string $service_name,
        string $host
    ): array {
        $path = is_string($path) ? $path : '';

        return match ($network) {
            'ws', 'WS' => array_filter([
                'type' => 'ws',
                'path' => $path !== '' ? $path : null,
                'headers' => $headers !== [] ? $headers : ($host !== '' ? ['Host' => $host] : null),
                'early_data_header_name' => 'Sec-WebSocket-Protocol',
            ], static fn ($v) => $v !== null && $v !== ''),
            'httpupgrade' => array_filter([
                'type' => 'httpupgrade',
                'path' => $path !== '' ? $path : null,
                'headers' => $host !== '' ? ['Host' => $host] : ($headers !== [] ? $headers : null),
            ], static fn ($v) => $v !== null && $v !== ''),
            'grpc' => array_filter([
                'type' => 'grpc',
                'service_name' => $service_name !== '' ? $service_name : ($path !== '' ? $path : null),
                'idle_timeout' => '115s',
                'ping_timeout' => '15s',
            ], static fn ($v) => $v !== null && $v !== ''),
            default => [],
        };
    }
}
