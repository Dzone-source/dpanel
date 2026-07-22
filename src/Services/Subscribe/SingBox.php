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
 * Base layout from Hiddify-Panel (base_singbox_config.json.j2 + singbox.py),
 * with XrayR SoftBank-safe Trojan: plain TCP must NOT use HTTP transport
 * (Hiddify server maps tcp→http; SoftBank/XrayR Trojan is raw TLS — that mismatch
 * leaves Hiddify stuck on "Connecting...").
 */
final class SingBox extends Base
{
    public function getContent($user): string
    {
        $nodes = [];
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

        $config = $this->baseConfig($dns_direct_domains);
        $config['outbounds'] = array_merge($config['outbounds'], $nodes);

        $proxy_tags = [];
        foreach ($nodes as $n) {
            $tag = (string) ($n['tag'] ?? '');
            if ($tag !== '' && ! str_contains($tag, 'shadowtls-out')) {
                $proxy_tags[] = $tag;
            }
        }

        // Default to first node (not Auto): SoftBank urltest probes often hang Connecting.
        $default_tag = $proxy_tags[0] ?? 'direct';
        $select_outbounds = $proxy_tags === [] ? ['direct'] : array_merge(['Auto'], $proxy_tags);

        $select = [
            'type' => 'selector',
            'tag' => 'Select',
            'outbounds' => $select_outbounds,
            'default' => $default_tag,
            'interrupt_exist_connections' => false,
        ];
        $auto = [
            'type' => 'urltest',
            'tag' => 'Auto',
            'outbounds' => $proxy_tags === [] ? ['direct'] : $proxy_tags,
            'url' => 'https://www.gstatic.com/generate_204',
            'interval' => '10m',
            'tolerance' => 200,
        ];

        array_unshift($config['outbounds'], $select, $auto);
        $config['experimental']['cache_file']['cache_id'] = (string) ($user->uuid ?? ($_ENV['appName'] ?? 'DPanel'));

        return (string) json_encode($config, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    /**
     * Hiddify-Panel base template + SoftBank mobile-safe TUN/DNS.
     */
    private function baseConfig(array $dns_direct_domains): array
    {
        return [
            'log' => [
                'disabled' => false,
                'level' => 'warn',
                'timestamp' => true,
            ],
            'outbounds' => [
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
                'final' => 'Select',
                'rule_set' => [],
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
                    // Keep DNS resolvers off the proxy path (bootstrap).
                    [
                        'ip_cidr' => ['8.8.8.8/32', '1.1.1.1/32'],
                        'outbound' => 'direct',
                    ],
                    [
                        'protocol' => 'quic',
                        'port' => [443],
                        'action' => 'reject',
                    ],
                    [
                        'ip_cidr' => ['224.0.0.0/3', 'ff00::/8'],
                        'outbound' => 'block',
                        'source_ip_cidr' => ['224.0.0.0/3', 'ff00::/8'],
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
                    'external_ui_download_url' => 'https://github.com/MetaCubeX/Yacd-meta/archive/gh-pages.zip',
                ],
                'cache_file' => [
                    'enabled' => true,
                    'path' => 'cache.db',
                    'cache_id' => '',
                    'store_fakeip' => true,
                ],
            ],
            'dns' => [
                'servers' => [
                    // Bootstrap / node hostnames — TCP DNS on direct (SoftBank UDP DNS often broken).
                    [
                        'tag' => 'dns-local',
                        'address' => 'tcp://8.8.8.8',
                        'detour' => 'direct',
                    ],
                    [
                        'tag' => 'dns-local-backup',
                        'address' => 'tcp://1.1.1.1',
                        'detour' => 'direct',
                    ],
                    // Optional remote via proxy (Hiddify); final stays dns-local to avoid chicken-egg.
                    [
                        'tag' => 'dns-remote',
                        'address' => 'tcp://1.1.1.1',
                        'address_resolver' => 'dns-local',
                        'strategy' => 'prefer_ipv4',
                        'detour' => 'Select',
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
                    // Resolve proxy dial hostnames via direct TCP DNS (never system under TUN).
                    [
                        'outbound' => ['any'],
                        'server' => 'dns-local',
                    ],
                ],
                'final' => 'dns-local',
                'reverse_mapping' => true,
                'strategy' => 'prefer_ipv4',
                'independent_cache' => true,
            ],
            'inbounds' => [
                [
                    'listen' => '127.0.0.1',
                    'listen_port' => 6450,
                    'override_address' => '8.8.8.8',
                    'override_port' => 53,
                    'tag' => 'dns-in',
                    'type' => 'direct',
                ],
                [
                    'type' => 'tun',
                    'tag' => 'tun-in',
                    'interface_name' => 'tun0',
                    'inet4_address' => '172.19.0.1/30',
                    // SoftBank 4G/5G: Hiddify default 9000 often PMTU black-holes → timeout.
                    'mtu' => 1400,
                    'auto_route' => true,
                    'strict_route' => false,
                    'stack' => 'mixed',
                    'sniff' => true,
                    'sniff_override_destination' => false,
                    'endpoint_independent_nat' => true,
                    'domain_strategy' => 'prefer_ipv4',
                ],
                [
                    'domain_strategy' => 'prefer_ipv4',
                    'listen' => '127.0.0.1',
                    'listen_port' => 2334,
                    'sniff' => true,
                    'sniff_override_destination' => false,
                    'tag' => 'mixed-in',
                    'type' => 'mixed',
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
        $host = (string) ($cfg['host'] ?? '');
        $allow_insecure = $this->resolveInsecure($cfg, $host, (string) $node_raw->server);

        $tls = [
            'enabled' => true,
            'insecure' => $allow_insecure,
            'alpn' => $this->resolveAlpn($cfg, 'h3'),
        ];
        if ($host !== '') {
            $tls['server_name'] = $host;
        }

        return [
            'type' => 'tuic',
            'tag' => $node_raw->name,
            'server' => $node_raw->server,
            'server_port' => (int) $port,
            'uuid' => $user->uuid,
            'password' => $user->passwd,
            'congestion_control' => $cfg['congestion_control'] ?? 'cubic',
            'udp_relay_mode' => 'native',
            'zero_rtt_handshake' => false,
            'heartbeat' => '10s',
            'tls' => $tls,
        ];
    }

    private function buildVmess($user, $node_raw, array $cfg): array
    {
        $port = $cfg['offset_port_user'] ?? ($cfg['offset_port_node'] ?? 443);
        $network = (string) ($cfg['network'] ?? 'tcp');
        if ($network === '' || $network === 'none') {
            $network = 'tcp';
        }
        $host = (string) ($cfg['header']['request']['headers']['Host'][0] ?? $cfg['host'] ?? '');
        $path = (string) ($cfg['header']['request']['path'][0] ?? $cfg['path'] ?? '');
        $headers = $cfg['header']['request']['headers'] ?? [];
        $service_name = (string) ($cfg['servicename'] ?? $cfg['grpc_service_name'] ?? '');
        $security = (string) ($cfg['security'] ?? 'none');
        $tls_enabled = $security === 'tls' || $security === 'auto';
        if ($security === 'none') {
            $tls_enabled = false;
        }

        $node = [
            'type' => 'vmess',
            'tag' => $node_raw->name,
            'server' => $node_raw->server,
            'server_port' => (int) $port,
            'uuid' => $user->uuid,
            'alter_id' => 0,
            'security' => $cfg['encryption'] ?? $cfg['cipher'] ?? 'auto',
            'packet_encoding' => 'xudp',
        ];

        if ($tls_enabled) {
            $this->applyTls($node, $cfg, $host, $network, (string) $node_raw->server);
        }

        $this->applyTransport($node, $network, $path, $headers, $service_name, $host);

        return $node;
    }

    private function buildTrojan($user, $node_raw, array $cfg): array
    {
        $port = $cfg['offset_port_user'] ?? ($cfg['offset_port_node'] ?? 443);
        $host = (string) ($cfg['host'] ?? '');
        $network = (string) ($cfg['network'] ?? 'tcp');
        if ($network === '' || $network === 'none') {
            $network = 'tcp';
        }
        $path = (string) ($cfg['header']['request']['path'][0] ?? $cfg['path'] ?? '');
        $headers = $cfg['header']['request']['headers'] ?? [];
        $service_name = (string) ($cfg['servicename'] ?? $cfg['grpc_service_name'] ?? '');

        $node = [
            'type' => 'trojan',
            'tag' => $node_raw->name,
            'server' => $node_raw->server,
            'server_port' => (int) $port,
            'password' => $user->uuid,
            'domain_strategy' => 'prefer_ipv4',
            'connect_timeout' => '20s',
            'tcp_fast_open' => false,
            'tcp_keep_alive' => '30s',
        ];

        $this->applyTls($node, $cfg, $host, $network, (string) $node_raw->server);
        $this->applyTransport($node, $network, $path, $headers, $service_name, $host);

        return $node;
    }

    private function applyTls(array &$node, array $cfg, string $host, string $network, string $server): void
    {
        $fingerprint = (string) ($cfg['fingerprint'] ?? $cfg['fp'] ?? 'chrome');
        if ($fingerprint === '' || $fingerprint === 'none') {
            $fingerprint = 'chrome';
        }

        // SoftBank fake-SNI: always insecure. Plain TCP: omit ALPN (h2 stalls Connecting).
        $tls = [
            'enabled' => true,
            'insecure' => true,
            'utls' => [
                'enabled' => true,
                'fingerprint' => $fingerprint,
            ],
        ];

        if ($network === 'tcp') {
            // Only set ALPN if admin explicitly configured it.
            if (isset($cfg['alpn']) && $cfg['alpn'] !== '' && $cfg['alpn'] !== []) {
                $tls['alpn'] = $this->resolveAlpn($cfg, 'http/1.1');
            }
        } else {
            $tls['alpn'] = $this->resolveAlpn($cfg, $this->defaultAlpnForNetwork($network));
        }

        if ($host !== '') {
            $tls['server_name'] = $host;
        }

        $node['tls'] = $tls;
    }

    /**
     * XrayR SoftBank: plain TCP = no transport object.
     * HTTP transport only when network is explicitly h2/http, or tcp WITH a path
     * (Hiddify-style HTTP camouflage). Empty-path tcp→http causes Connecting timeout.
     */
    private function applyTransport(
        array &$node,
        string $network,
        string $path,
        array $headers,
        string $service_name,
        string $host
    ): void {
        if ($network === 'ws' || $network === 'WS') {
            $transport = [
                'type' => 'ws',
                'path' => $path !== '' ? $path : '/',
                'early_data_header_name' => 'Sec-WebSocket-Protocol',
            ];
            if ($host !== '') {
                $transport['headers'] = ['Host' => $host];
            } elseif ($headers !== []) {
                $transport['headers'] = $headers;
            }
            $node['transport'] = $transport;

            return;
        }

        if ($network === 'httpupgrade') {
            $transport = [
                'type' => 'httpupgrade',
                'path' => $path !== '' ? $path : '/',
            ];
            if ($host !== '') {
                $transport['headers'] = ['Host' => $host];
            }
            $node['transport'] = $transport;

            return;
        }

        if ($network === 'h2' || $network === 'http' || ($network === 'tcp' && $path !== '')) {
            $transport = [
                'type' => 'http',
                'path' => $path,
                'idle_timeout' => '15s',
                'ping_timeout' => '15s',
            ];
            if ($host !== '') {
                $transport['host'] = [$host];
            }
            $node['transport'] = $transport;

            return;
        }

        // Plain tcp with empty path: omit transport (raw Trojan/VMess over TLS).
        if ($network === 'tcp') {
            return;
        }

        if ($network === 'grpc') {
            $node['transport'] = [
                'type' => 'grpc',
                'service_name' => $service_name !== '' ? $service_name : $path,
                'idle_timeout' => '115s',
                'ping_timeout' => '15s',
            ];
        }
    }

    private function defaultAlpnForNetwork(string $network): string
    {
        if ($network === 'grpc' || $network === 'h2') {
            return 'h2';
        }

        return 'http/1.1';
    }

    private function resolveAlpn(array $cfg, string $default): array
    {
        $alpn = $cfg['alpn'] ?? $default;
        if (is_array($alpn)) {
            return array_values(array_filter(array_map('strval', $alpn)));
        }

        return array_values(array_filter(array_map('trim', explode(',', (string) $alpn))));
    }

    private function resolveInsecure(array $cfg, string $host, string $server): bool
    {
        if (filter_var($cfg['allow_insecure'] ?? false, FILTER_VALIDATE_BOOLEAN)) {
            return true;
        }
        $mode = (string) ($cfg['mode'] ?? '');
        if (strcasecmp($mode, 'Fake') === 0) {
            return true;
        }
        if ($host !== '' && strcasecmp($host, $server) !== 0) {
            return true;
        }

        return false;
    }
}
