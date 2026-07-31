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
                    $trojan_port = NodeConfig::port($node_custom_config);
                    $host = NodeConfig::host($node_custom_config);
                    $allow_insecure = NodeConfig::allowInsecure($node_custom_config);
                    $transport = $node_custom_config['network'] ?? '';
                    $path = NodeConfig::path($node_custom_config);
                    $headers = $node_custom_config['header']['request']['headers'] ?? [];
                    $service_name = $node_custom_config['servicename'] ?? '';

                    $tls = [
                        'enabled' => true,
                        'server_name' => $host,
                        'insecure' => $allow_insecure,
                    ];

                    if (NodeConfig::isReality($node_custom_config)) {
                        $reality = NodeConfig::realityClient($node_custom_config);
                        $tls['server_name'] = $reality['server_name'] !== '' ? $reality['server_name'] : $host;
                        $tls['utls'] = [
                            'enabled' => true,
                            'fingerprint' => $reality['fingerprint'],
                        ];
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
                        'server_port' => $trojan_port,
                        'password' => $user->uuid,
                        'tls' => array_filter($tls, static fn ($v) => $v !== null && $v !== ''),
                        'transport' => array_filter([
                            'type' => $transport,
                            'path' => $path,
                            'headers' => $headers,
                            'service_name' => $service_name,
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
}
