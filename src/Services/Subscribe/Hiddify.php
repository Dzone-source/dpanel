<?php

declare(strict_types=1);

namespace App\Services\Subscribe;

use App\Services\Subscribe;
use function array_filter;
use function array_map;
use function array_merge;
use function array_values;
use function explode;
use function is_array;
use function is_string;
use function json_encode;
use function trim;
use const JSON_UNESCAPED_SLASHES;
use const JSON_UNESCAPED_UNICODE;

/**
 * Dedicated Hiddify-app / HiddifyNext profile.
 *
 * Mirrors HiddifyPanel behaviour for UA HiddifyNext|Dart|SFI|SFA:
 * return a **minimal Sing-box JSON** (base_singbox_config + outbounds),
 * NOT the heavy SSPanel SingBox_Config and NOT base64 allshare.
 *
 * @see https://github.com/hiddify/HiddifyPanel/blob/main/hiddifypanel/panel/user/user.py
 * @see https://github.com/hiddify/HiddifyPanel/blob/main/hiddifypanel/panel/user/templates/base_singbox_config.json.j2
 * @see https://github.com/hiddify/HiddifyPanel/blob/main/hiddifypanel/hutils/proxy/singbox.py
 */
final class Hiddify extends Base
{
    public function getContent($user): string
    {
        $proxies = $this->buildOutbounds($user);
        $proxyTags = [];
        foreach ($proxies as $p) {
            $proxyTags[] = $p['tag'];
        }

        // Always keep Select/Auto even if empty — app still creates a profile;
        // empty proxy list shows clearly vs a parse error.
        $selectOutbounds = array_values(array_merge(['Auto'], $proxyTags));
        $autoOutbounds = $proxyTags !== [] ? $proxyTags : ['direct'];

        $config = [
            'log' => [
                'level' => 'warn',
            ],
            'dns' => [
                'servers' => [
                    [
                        'tag' => 'dns-remote',
                        'address' => 'tcp://1.1.1.1',
                        'address_resolver' => 'dns-local',
                        'strategy' => 'prefer_ipv4',
                        'detour' => 'Select',
                    ],
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
                        'domain' => [
                            'github.com',
                            'githubusercontent.com',
                            'raw.githubusercontent.com',
                            '1.1.1.1',
                        ],
                        'server' => 'dns-local',
                    ],
                    [
                        'outbound' => 'direct',
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
                    'address' => ['172.19.0.1/30'],
                    'mtu' => 9000,
                    'auto_route' => true,
                    'strict_route' => true,
                    'stack' => 'system',
                    'endpoint_independent_nat' => true,
                ],
                [
                    'type' => 'mixed',
                    'tag' => 'mixed-in',
                    'listen' => '127.0.0.1',
                    'listen_port' => 2334,
                    'sniff' => true,
                    'sniff_override_destination' => false,
                    'domain_strategy' => 'prefer_ipv4',
                ],
            ],
            'outbounds' => array_merge(
                [
                    [
                        'type' => 'selector',
                        'tag' => 'Select',
                        'outbounds' => $selectOutbounds,
                        'default' => 'Auto',
                    ],
                    [
                        'type' => 'urltest',
                        'tag' => 'Auto',
                        'outbounds' => $autoOutbounds,
                        'url' => 'https://www.gstatic.com/generate_204',
                        'interval' => '10m',
                        'tolerance' => 200,
                    ],
                    [
                        'type' => 'direct',
                        'tag' => 'direct',
                    ],
                    [
                        'type' => 'direct',
                        'tag' => 'bypass',
                    ],
                ],
                $proxies
            ),
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
                    [
                        'protocol' => 'quic',
                        'port' => [443],
                        'action' => 'reject',
                    ],
                    [
                        'ip_cidr' => ['224.0.0.0/3', 'ff00::/8'],
                        'source_ip_cidr' => ['224.0.0.0/3', 'ff00::/8'],
                        'outbound' => 'bypass',
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
                    'cache_id' => (string) ($user->uuid ?? 'dpanel'),
                    'store_fakeip' => true,
                ],
            ],
        ];

        return (string) json_encode($config, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    /**
     * Build Sing-box outbounds the same way HiddifyPanel `to_singbox()` does.
     *
     * @return list<array<string, mixed>>
     */
    private function buildOutbounds($user): array
    {
        $out = [];
        $nodes = Subscribe::getUserNodes($user);

        foreach ($nodes as $node) {
            $cfg = NodeConfig::decode($node->custom_config);
            $sort = (int) $node->sort;

            if ($sort === 14) {
                $proxy = $this->trojanOutbound($node, $user, $cfg);
                if ($proxy !== null) {
                    $out[] = $proxy;
                }
            } elseif ($sort === 11) {
                $proxy = $this->v2Outbound($node, $user, $cfg);
                if ($proxy !== null) {
                    $out[] = $proxy;
                }
            }
        }

        return $out;
    }

    private function trojanOutbound(object $node, object $user, array $cfg): ?array
    {
        $password = NodeConfig::trojanPassword($user);
        if ($password === '') {
            return null;
        }

        $sni = NodeConfig::sni($cfg, (string) $node->server);
        $network = (string) ($cfg['network'] ?? 'tcp');
        if ($network === '') {
            $network = 'tcp';
        }

        $outbound = [
            'type' => 'trojan',
            'tag' => (string) $node->name,
            'server' => (string) $node->server,
            'server_port' => NodeConfig::port($cfg),
            'password' => $password,
            'tls' => $this->tlsBlock($cfg, $sni),
        ];

        $transport = $this->transportBlock($cfg, $network, $sni);
        if ($transport !== null) {
            $outbound['transport'] = $transport;
        }

        return $outbound;
    }

    private function v2Outbound(object $node, object $user, array $cfg): ?array
    {
        $uuid = (string) ($user->uuid ?? '');
        if ($uuid === '') {
            return null;
        }

        $isVless = NodeConfig::isVless($cfg);
        $sni = NodeConfig::sni($cfg, (string) $node->server);
        $network = (string) ($cfg['network'] ?? 'tcp');
        if ($network === '') {
            $network = 'tcp';
        }
        $security = NodeConfig::security($cfg);
        $tlsEnabled = $security === 'tls' || $security === 'xtls' || $security === 'reality';

        $outbound = [
            'type' => $isVless ? 'vless' : 'vmess',
            'tag' => (string) $node->name,
            'server' => (string) $node->server,
            'server_port' => NodeConfig::port($cfg),
            'uuid' => $uuid,
            'packet_encoding' => 'xudp',
        ];

        if ($isVless) {
            $flow = NodeConfig::flow($cfg);
            if ($flow !== '') {
                $outbound['flow'] = $flow;
            }
        } else {
            $outbound['alter_id'] = 0;
            $outbound['security'] = 'auto';
        }

        if ($tlsEnabled) {
            $outbound['tls'] = $this->tlsBlock($cfg, $sni);
        }

        $transport = $this->transportBlock($cfg, $network, $sni);
        if ($transport !== null) {
            $outbound['transport'] = $transport;
        }

        return $outbound;
    }

    /**
     * @return array<string, mixed>
     */
    private function tlsBlock(array $cfg, string $sni): array
    {
        $fp = NodeConfig::fingerprint($cfg);
        if ($fp === '' || $fp === 'none') {
            $fp = 'chrome';
        }

        // Match HiddifyPanel ProxyTLS.singbox_tls / add_tls — no forced alpn
        // (hardcoding h2 can break plain Trojan TLS that only negotiates http/1.1).
        $tls = [
            'enabled' => true,
            'server_name' => $sni,
            'insecure' => NodeConfig::allowInsecure($cfg),
            'utls' => [
                'enabled' => true,
                'fingerprint' => $fp,
            ],
        ];

        $alpn = $cfg['alpn'] ?? null;
        if (is_string($alpn) && $alpn !== '') {
            $tls['alpn'] = array_values(array_filter(array_map('trim', explode(',', $alpn))));
        } elseif (is_array($alpn) && $alpn !== []) {
            $tls['alpn'] = array_values($alpn);
        }

        if (NodeConfig::isReality($cfg)) {
            $reality = NodeConfig::realityClient($cfg);
            if ($reality['server_name'] !== '') {
                $tls['server_name'] = $reality['server_name'];
            }
            $tls['reality'] = [
                'enabled' => true,
                'public_key' => $reality['public_key'],
                'short_id' => $reality['short_id'],
            ];
            $tls['utls']['fingerprint'] = $reality['fingerprint'] !== '' ? $reality['fingerprint'] : $fp;
        }

        return $tls;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function transportBlock(array $cfg, string $network, string $sni): ?array
    {
        // HiddifyPanel: for reality + non-grpc, often omit transport.
        if (NodeConfig::isReality($cfg) && $network !== 'grpc') {
            return null;
        }

        if ($network === '' || $network === 'tcp') {
            return null;
        }

        if ($network === 'ws' || $network === 'httpupgrade') {
            $path = NodeConfig::path($cfg, '/');
            $transport = [
                'type' => $network === 'httpupgrade' ? 'httpupgrade' : 'ws',
                'path' => $path !== '' ? $path : '/',
            ];
            $host = NodeConfig::host($cfg);
            if ($host === '') {
                $host = $sni;
            }
            if ($host !== '') {
                $transport['headers'] = ['Host' => $host];
            }
            if ($network === 'ws') {
                $transport['early_data_header_name'] = 'Sec-WebSocket-Protocol';
            }

            return $transport;
        }

        if ($network === 'grpc') {
            $service = (string) ($cfg['servicename'] ?? $cfg['serviceName'] ?? '');
            $transport = ['type' => 'grpc'];
            if ($service !== '') {
                $transport['service_name'] = $service;
            }

            return $transport;
        }

        return ['type' => $network];
    }

    /** @deprecated kept for tests / debug */
    public function getPlainProfile($user): string
    {
        return $this->getContent($user);
    }
}
