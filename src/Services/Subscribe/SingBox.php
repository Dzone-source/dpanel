<?php

declare(strict_types=1);

namespace App\Services\Subscribe;

use App\Services\Subscribe;
use App\Utils\Tools;
use function array_filter;
use function array_merge;
use function json_decode;
use function json_encode;

final class SingBox extends Base
{
    public function getContent($user): string
    {
        $nodes = [];
        $singbox_config = $_ENV['SingBox_Config'];
        $nodes_raw = Subscribe::getUserNodes($user);

        foreach ($nodes_raw as $node_raw) {
            $node_custom_config = json_decode($node_raw->custom_config, true);

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
                    $ss_2022_port = $node_custom_config['offset_port_user'] ??
                        ($node_custom_config['offset_port_node'] ?? 443);
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
                        'server_port' => (int) $ss_2022_port,
                        'method' => $method,
                        'password' => $server_key === '' ? $user_pk : $server_key . ':' .$user_pk,
                        'udp_over_tcp' => (bool) $uot,
                    ];

                    break;
                case 2:
                    $tuic_port = $node_custom_config['offset_port_user'] ??
                        ($node_custom_config['offset_port_node'] ?? 443);
                    $host = $node_custom_config['host'] ?? '';
                    $allow_insecure = $node_custom_config['allow_insecure'] ?? false;
                    $congestion_control = $node_custom_config['congestion_control'] ?? 'bbr';

                    $node = [
                        'type' => 'tuic',
                        'tag' => $node_raw->name,
                        'server' => $node_raw->server,
                        'server_port' => (int) $tuic_port,
                        'uuid' => $user->uuid,
                        'password' => $user->passwd,
                        'congestion_control' => $congestion_control,
                        'zero_rtt_handshake' => true,
                        'tls' => [
                            'enabled' => true,
                            'server_name' => $host,
                            'insecure' => (bool) $allow_insecure,
                        ],
                    ];

                    $node['tls'] = array_filter($node['tls']);

                    break;
                case 11:
                    $v2_port = $node_custom_config['offset_port_user'] ??
                        ($node_custom_config['offset_port_node'] ?? 443);
                    $transport = ($node_custom_config['network'] ?? '') === 'tcp' ? '' : $node_custom_config['network'];
                    $host = $node_custom_config['header']['request']['headers']['Host'][0] ??
                        $node_custom_config['host'] ?? '';
                    $path = $node_custom_config['header']['request']['path'][0] ?? $node_custom_config['path'] ?? '';
                    $headers = $node_custom_config['header']['request']['headers'] ?? [];
                    $service_name = $node_custom_config['servicename'] ?? '';
                    $utls = $node_custom_config['utls'] ?? false;
                    $method = $node_custom_config['method'] ?? '';
                    $max_early_data = $node_custom_config['max_early_data'] ?? '';
                    $early_data_header_name = $node_custom_config['early_data_header_name'] ?? '';

                    $node = [
                        'type' => 'vmess',
                        'tag' => $node_raw->name,
                        'server' => $node_raw->server,
                        'server_port' => (int) $v2_port,
                        'uuid' => $user->uuid,
                        'security' => 'auto',
                        'alter_id' => 0,
                        'tls' => [
                            'enabled' => true,
                            'server_name' => $host,
                            'utls' => [
                                'enabled' => $utls,
                                'fingerprint' => 'chrome',
                            ],
                        ],
                        'packet_encoding' => 'xudp',
                        'global_padding' => true,
                        'authenticated_length' => true,
                        'transport' => [
                            'type' => $transport,
                            'path' => $path,
                            'method' => $method,
                            'headers' => $headers,
                            'service_name' => $service_name,
                            'max_early_data' => (int) $max_early_data,
                            'early_data_header_name' => $early_data_header_name,
                        ],
                    ];

                    $node['tls'] = array_filter($node['tls']);
                    $node['transport'] = array_filter($node['transport']);

                    break;
                case 14:
                    $trojan_port = $node_custom_config['offset_port_user'] ??
                        ($node_custom_config['offset_port_node'] ?? 443);
                    $host = $node_custom_config['host'] ?? '';
                    $allow_insecure = $node_custom_config['allow_insecure'] ?? '0';
                    $transport = $node_custom_config['network'] ?? '';
                    $path = $node_custom_config['header']['request']['path'][0] ?? $node_custom_config['path'] ?? '';
                    $headers = $node_custom_config['header']['request']['headers'] ?? [];
                    $service_name = $node_custom_config['servicename'] ?? '';

                    $node = [
                        'type' => 'trojan',
                        'tag' => $node_raw->name,
                        'server' => $node_raw->server,
                        'server_port' => (int) $trojan_port,
                        'password' => $user->uuid,
                        'tls' => [
                            'enabled' => true,
                            'server_name' => $host,
                            'insecure' => (bool) $allow_insecure,
                        ],
                        'transport' => [
                            'type' => $transport,
                            'path' => $path,
                            'headers' => $headers,
                            'service_name' => $service_name,
                        ],
                    ];

                    $node['tls'] = array_filter($node['tls']);
                    $node['transport'] = array_filter($node['transport']);

                    break;
                case 12:
                    // VLESS (+ optional Reality)
                    $vless_port = NodeConfigHelper::port($node_custom_config);
                    $network = NodeConfigHelper::network($node_custom_config, 'tcp');
                    $host = NodeConfigHelper::host($node_custom_config);
                    $path = NodeConfigHelper::path($node_custom_config);
                    $headers = $node_custom_config['header']['request']['headers'] ?? [];
                    $security = NodeConfigHelper::security($node_custom_config);
                    $flow = NodeConfigHelper::flow($node_custom_config);
                    $allow_insecure = NodeConfigHelper::allowInsecure($node_custom_config);
                    $service_name = NodeConfigHelper::serviceName($node_custom_config);
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
                                'fingerprint' => NodeConfigHelper::fingerprint($node_custom_config),
                            ],
                        ];

                        if ($security === 'reality') {
                            $tls['reality'] = [
                                'enabled' => true,
                                'public_key' => NodeConfigHelper::publicKey($node_custom_config),
                                'short_id' => NodeConfigHelper::shortId($node_custom_config),
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
                    $hy_port = NodeConfigHelper::port($node_custom_config);
                    $host = NodeConfigHelper::host($node_custom_config);
                    $allow_insecure = NodeConfigHelper::allowInsecure($node_custom_config);
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
                    $any_port = NodeConfigHelper::port($node_custom_config);
                    $host = NodeConfigHelper::host($node_custom_config);
                    $allow_insecure = NodeConfigHelper::allowInsecure($node_custom_config);

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
}
