<?php

declare(strict_types=1);

namespace App\Services\Subscribe;

use App\Services\Subscribe;
use App\Utils\Tools;
use function array_filter;
use function array_merge;
use function json_encode;

final class SingBox extends Base
{
    public function getContent($user): string
    {
        $nodes = [];
        $singbox_config = $_ENV['SingBox_Config'];
        $nodes_raw = Subscribe::getUserNodes($user);

        foreach ($nodes_raw as $node_raw) {
            $node_custom_config = NodeConfig::decode($node_raw->custom_config);

            switch ((int) $node_raw->sort) {
                case 0:
                    $node = [
                        'type' => 'shadowsocks',
                        'tag' => $node_raw->name,
                        'server' => $node_raw->server,
                        'server_port' => (int) $user->port,
                        'method' => $user->method,
                        'password' => $user->passwd,
                    ];

                    break;
                case 1:
                    $ss_2022_port = NodeConfig::port($node_custom_config);
                    $method = $node_custom_config['method'] ?? '2022-blake3-aes-128-gcm';
                    $user_pk = Tools::genSs2022UserPk($user->passwd, $method);
                    $uot = $node_custom_config['uot'] ?? false;

                    if (! $user_pk) {
                        $node = [];
                        break;
                    }

                    $server_key = $node_custom_config['server_key'] ?? '';

                    $node = [
                        'type' => 'shadowsocks',
                        'tag' => $node_raw->name,
                        'server' => $node_raw->server,
                        'server_port' => $ss_2022_port,
                        'method' => $method,
                        'password' => $server_key === '' ? $user_pk : $server_key . ':' . $user_pk,
                        'udp_over_tcp' => (bool) $uot,
                    ];

                    break;
                case 2:
                    $tuic_port = NodeConfig::port($node_custom_config);
                    $host = NodeConfig::host($node_custom_config);
                    $allow_insecure = NodeConfig::allowInsecure($node_custom_config);
                    $congestion_control = $node_custom_config['congestion_control'] ?? 'bbr';

                    $node = [
                        'type' => 'tuic',
                        'tag' => $node_raw->name,
                        'server' => $node_raw->server,
                        'server_port' => $tuic_port,
                        'uuid' => $user->uuid,
                        'password' => $user->passwd,
                        'congestion_control' => $congestion_control,
                        'zero_rtt_handshake' => true,
                        'tls' => [
                            'enabled' => true,
                            'server_name' => $host,
                            'insecure' => $allow_insecure,
                        ],
                    ];

                    $node['tls'] = array_filter($node['tls'], static fn ($v) => $v !== null && $v !== '');

                    break;
                case 11:
                    $node = $this->buildV2Family($node_raw, $user, $node_custom_config);
                    break;
                case 14:
                    $node = $this->buildTrojan($node_raw, $user, $node_custom_config);
                    break;
                case 12:
                    // VLESS (+ optional Reality)
                    $vless_port = NodeConfig::port($node_custom_config);
                    $network = NodeConfig::network($node_custom_config, 'tcp');
                    $host = NodeConfig::host($node_custom_config);
                    $path = NodeConfig::path($node_custom_config);
                    $headers = $node_custom_config['header']['request']['headers'] ?? [];
                    $security = NodeConfig::security($node_custom_config);
                    $flow = NodeConfig::flow($node_custom_config);
                    $allow_insecure = NodeConfig::allowInsecure($node_custom_config);
                    $service_name = NodeConfig::serviceName($node_custom_config);
                    $transport = $network === 'tcp' ? '' : $network;

                    $node = [
                        'type' => 'vless',
                        'tag' => $node_raw->name,
                        'server' => $node_raw->server,
                        'server_port' => $vless_port,
                        'uuid' => $user->uuid,
                        'packet_encoding' => 'xudp',
                    ];

                    if ($flow !== '') {
                        $node['flow'] = $flow;
                    }

                    if (in_array($security, ['tls', 'reality'], true)) {
                        $tls = [
                            'enabled' => true,
                            'server_name' => $host,
                            'insecure' => $allow_insecure,
                            'utls' => [
                                'enabled' => true,
                                'fingerprint' => NodeConfig::fingerprint($node_custom_config),
                            ],
                        ];

                        if ($security === 'reality') {
                            $tls['reality'] = [
                                'enabled' => true,
                                'public_key' => NodeConfig::publicKey($node_custom_config),
                                'short_id' => NodeConfig::shortId($node_custom_config),
                            ];
                        }

                        $node['tls'] = array_filter($tls);
                    }

                    if ($transport !== '') {
                        $node['transport'] = array_filter([
                            'type' => $transport,
                            'path' => $path,
                            'headers' => $headers,
                            'service_name' => $service_name,
                        ]);
                    }

                    break;
                case 13:
                    // Hysteria2
                    $hy_port = NodeConfig::port($node_custom_config);
                    $host = NodeConfig::host($node_custom_config);
                    $allow_insecure = NodeConfig::allowInsecure($node_custom_config);
                    $up = (int) ($node_custom_config['up_mbps'] ?? $node_custom_config['up'] ?? 100);
                    $down = (int) ($node_custom_config['down_mbps'] ?? $node_custom_config['down'] ?? 100);
                    $obfs = (string) ($node_custom_config['obfs'] ?? '');
                    $obfs_password = (string) ($node_custom_config['obfs_password'] ?? $node_custom_config['obfs-password'] ?? '');

                    $node = [
                        'type' => 'hysteria2',
                        'tag' => $node_raw->name,
                        'server' => $node_raw->server,
                        'server_port' => $hy_port,
                        'password' => $user->uuid,
                        'up_mbps' => $up,
                        'down_mbps' => $down,
                        'tls' => array_filter([
                            'enabled' => true,
                            'server_name' => $host !== '' ? $host : null,
                            'insecure' => $allow_insecure,
                        ]),
                    ];

                    if (isset($node_custom_config['ports']) && $node_custom_config['ports'] !== '') {
                        $node['server_ports'] = [str_replace('-', ':', (string) $node_custom_config['ports'])];
                    }
                    if (isset($node_custom_config['hop_interval'])) {
                        $node['hop_interval'] = ((int) $node_custom_config['hop_interval']) . 's';
                    }
                    if ($obfs !== '') {
                        $node['obfs'] = array_filter([
                            'type' => $obfs,
                            'password' => $obfs_password !== '' ? $obfs_password : null,
                        ]);
                    }

                    break;
                case 15:
                    // AnyTLS
                    $any_port = NodeConfig::port($node_custom_config);
                    $host = NodeConfig::host($node_custom_config);
                    $allow_insecure = NodeConfig::allowInsecure($node_custom_config);

                    $node = [
                        'type' => 'anytls',
                        'tag' => $node_raw->name,
                        'server' => $node_raw->server,
                        'server_port' => $any_port,
                        'password' => $user->uuid,
                        'tls' => array_filter([
                            'enabled' => true,
                            'server_name' => $host !== '' ? $host : null,
                            'insecure' => $allow_insecure,
                        ]),
                    ];

                    break;
                default:
                    $node = [];
                    break;
            }

            if ($node === []) {
                continue;
            }

            $nodes[] = $node;
            $singbox_config['outbounds'][0]['outbounds'][] = $node_raw->name;
            $singbox_config['outbounds'][1]['outbounds'][] = $node_raw->name;
        }

        $singbox_config['outbounds'] = array_merge($singbox_config['outbounds'], $nodes);
        $singbox_config['experimental']['cache_file']['cache_id'] = $_ENV['appName'];

        return json_encode($singbox_config);
    }

    private function buildV2Family(object $node_raw, object $user, array $cfg): array
    {
        $v2_port = NodeConfig::port($cfg);
        $network = (string) ($cfg['network'] ?? 'tcp');
        $host = NodeConfig::host($cfg);
        $path = NodeConfig::path($cfg);
        $headers = $cfg['header']['request']['headers'] ?? [];
        $service_name = $cfg['servicename'] ?? $cfg['serviceName'] ?? '';
        $utls = NodeConfig::isTruthy($cfg['utls'] ?? false) || NodeConfig::isReality($cfg) || NodeConfig::security($cfg) === 'tls';
        $method = $cfg['method'] ?? '';
        $max_early_data = $cfg['max_early_data'] ?? '';
        $early_data_header_name = $cfg['early_data_header_name'] ?? '';
        $isVless = NodeConfig::isVless($cfg);
        $security = NodeConfig::security($cfg);
        $allow_insecure = NodeConfig::allowInsecure($cfg);

        $transportType = ($network === '' || $network === 'tcp') ? '' : $network;
        if ($network === 'httpupgrade') {
            $transportType = 'httpupgrade';
        }

        $node = [
            'type' => $isVless ? 'vless' : 'vmess',
            'tag' => $node_raw->name,
            'server' => $node_raw->server,
            'server_port' => $v2_port,
            'uuid' => $user->uuid,
            'packet_encoding' => 'xudp',
        ];

        if (! $isVless) {
            $node['security'] = 'auto';
            $node['alter_id'] = 0;
            $node['global_padding'] = true;
            $node['authenticated_length'] = true;
        } else {
            $flow = NodeConfig::flow($cfg);
            if ($flow !== '') {
                $node['flow'] = $flow;
            }
        }

        $tlsEnabled = $security === 'tls' || $security === 'xtls' || $security === 'reality';
        if ($tlsEnabled) {
            $tls = [
                'enabled' => true,
                'insecure' => $allow_insecure,
                'server_name' => $host,
                'utls' => [
                    'enabled' => (bool) $utls,
                    'fingerprint' => NodeConfig::fingerprint($cfg),
                ],
            ];

            if (NodeConfig::isReality($cfg)) {
                $reality = NodeConfig::realityClient($cfg);
                $tls['server_name'] = $reality['server_name'] !== '' ? $reality['server_name'] : $host;
                $tls['utls']['enabled'] = true;
                $tls['utls']['fingerprint'] = $reality['fingerprint'];
                $tls['reality'] = [
                    'enabled' => true,
                    'public_key' => $reality['public_key'],
                    'short_id' => $reality['short_id'],
                ];
            }

            $node['tls'] = array_filter($tls, static fn ($v) => $v !== null && $v !== '');
        }

        if ($transportType !== '') {
            $transport = array_filter([
                'type' => $transportType,
                'path' => $path,
                'method' => $method,
                'headers' => $headers,
                'service_name' => $service_name,
                'max_early_data' => $max_early_data === '' ? null : (int) $max_early_data,
                'early_data_header_name' => $early_data_header_name,
            ], static fn ($v) => $v !== null && $v !== '' && $v !== []);
            if ($transport !== []) {
                $node['transport'] = $transport;
            }
        }

        return $node;
    }

    /**
     * Sing-box Trojan outbound for Hiddify (password = UUID).
     */
    private function buildTrojan(object $node_raw, object $user, array $cfg): array
    {
        $password = NodeConfig::trojanPassword($user);
        if ($password === '') {
            return [];
        }

        $sni = NodeConfig::sni($cfg, (string) $node_raw->server);
        $allow_insecure = NodeConfig::allowInsecure($cfg);
        $network = (string) ($cfg['network'] ?? 'tcp');
        $path = NodeConfig::path($cfg);
        $headers = $cfg['header']['request']['headers'] ?? [];
        $service_name = $cfg['servicename'] ?? $cfg['serviceName'] ?? '';

        $tls = [
            'enabled' => true,
            'server_name' => $sni,
            'insecure' => $allow_insecure,
            'utls' => [
                'enabled' => true,
                'fingerprint' => NodeConfig::fingerprint($cfg),
            ],
        ];

        if (NodeConfig::isReality($cfg)) {
            $reality = NodeConfig::realityClient($cfg);
            $tls['server_name'] = $reality['server_name'] !== '' ? $reality['server_name'] : $sni;
            $tls['utls']['fingerprint'] = $reality['fingerprint'];
            $tls['reality'] = [
                'enabled' => true,
                'public_key' => $reality['public_key'],
                'short_id' => $reality['short_id'],
            ];
        }

        $node = [
            'type' => 'trojan',
            'tag' => $node_raw->name,
            'server' => $node_raw->server,
            'server_port' => NodeConfig::port($cfg),
            'password' => $password,
            'tls' => $tls,
        ];

        $transportType = ($network === '' || $network === 'tcp') ? '' : $network;
        if ($network === 'httpupgrade') {
            $transportType = 'httpupgrade';
        }

        if ($transportType !== '') {
            $transport = array_filter([
                'type' => $transportType,
                'path' => $path,
                'headers' => $headers,
                'service_name' => $service_name,
            ], static fn ($v) => $v !== null && $v !== '' && $v !== []);
            if ($transport !== []) {
                $node['transport'] = $transport;
            }
        }

        return $node;
    }
}
