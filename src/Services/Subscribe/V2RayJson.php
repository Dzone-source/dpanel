<?php

declare(strict_types=1);

namespace App\Services\Subscribe;

use App\Services\Subscribe;
use App\Utils\Tools;
use function array_filter;
use function array_merge;
use function json_encode;

final class V2RayJson extends Base
{
    public function getContent($user): string
    {
        $nodes = [];
        $v2rayjson_config = $_ENV['V2RayJson_Config'];
        $nodes_raw = Subscribe::getUserNodes($user);

        foreach ($nodes_raw as $node_raw) {
            $node_custom_config = NodeConfig::decode($node_raw->custom_config);

            switch ((int) $node_raw->sort) {
                case 0:
                    $node = [
                        'protocol' => 'shadowsocks',
                        'settings' => [
                            'address' => $node_raw->server,
                            'port' => (int) $user->port,
                            'method' => $user->method,
                            'password' => $user->passwd,
                        ],
                        'tag' => $node_raw->name,
                    ];

                    break;
                case 1:
                    $ss_2022_port = NodeConfig::port($node_custom_config);
                    $method = $node_custom_config['method'] ?? '2022-blake3-aes-128-gcm';
                    $user_pk = Tools::genSs2022UserPk($user->passwd, $method);

                    if (! $user_pk) {
                        $node = [];
                        break;
                    }

                    $server_key = $node_custom_config['server_key'] ?? '';

                    $node = [
                        'protocol' => 'shadowsocks2022',
                        'settings' => [
                            'address' => $node_raw->server,
                            'port' => $ss_2022_port,
                            'method' => $user->method,
                            'psk' => $server_key === '' ? $user_pk : $server_key . ':' . $user_pk,
                        ],
                        'tag' => $node_raw->name,
                    ];

                    break;
                case 11:
                    $node = $this->buildV2Family($node_raw, $user, $node_custom_config);
                    break;
                case 14:
                    $trojan_port = NodeConfig::port($node_custom_config);
                    $host = NodeConfig::host($node_custom_config, (string) $node_raw->server);
                    $allow_insecure = NodeConfig::allowInsecure($node_custom_config);
                    $transport = $node_custom_config['network'] ?? '';
                    $path = NodeConfig::path($node_custom_config);
                    $headers = $node_custom_config['header']['request']['headers'] ?? [];
                    $service_name = $node_custom_config['servicename'] ?? '';

                    $node = [
                        'protocol' => 'trojan',
                        'settings' => [
                            'address' => $node_raw->server,
                            'port' => $trojan_port,
                            'password' => $user->uuid,
                        ],
                        'tag' => $node_raw->name,
                        'streamSettings' => [
                            'transport' => $transport,
                            'transportSettings' => [
                                'ws' => array_filter([
                                    'path' => $transport === 'ws' ? $path : '',
                                    'header' => $headers,
                                ]),
                                'grpc' => array_filter([
                                    'host' => $transport === 'grpc' ? $host : '',
                                    'service_name' => $service_name,
                                ]),
                                'httpupgrade' => array_filter([
                                    'path' => $transport === 'httpupgrade' ? $path : '',
                                    'host' => $transport === 'httpupgrade' ? $host : '',
                                ]),
                            ],
                            'security' => NodeConfig::isReality($node_custom_config) ? 'reality' : 'tls',
                            'securitySettings' => [
                                'tls' => array_filter([
                                    'allow_insecure' => $allow_insecure,
                                    'server_name' => $host,
                                ]),
                            ],
                        ],
                    ];

                    if (NodeConfig::isReality($node_custom_config)) {
                        $reality = NodeConfig::realityClient($node_custom_config);
                        $node['streamSettings']['securitySettings']['reality'] = array_filter([
                            'server_name' => $reality['server_name'] !== '' ? $reality['server_name'] : $host,
                            'public_key' => $reality['public_key'],
                            'short_id' => $reality['short_id'],
                            'fingerprint' => $reality['fingerprint'],
                        ]);
                    }

                    $node['streamSettings']['transportSettings'] = array_filter($node['streamSettings']['transportSettings']);
                    $node['streamSettings']['securitySettings'] = array_filter($node['streamSettings']['securitySettings']);

                    break;
                default:
                    $node = [];
                    break;
            }

            if ($node === []) {
                continue;
            }

            $nodes[] = $node;
        }

        $v2rayjson_config['outbounds'] = array_merge($v2rayjson_config['outbounds'], $nodes);

        return json_encode($v2rayjson_config);
    }

    private function buildV2Family(object $node_raw, object $user, array $cfg): array
    {
        $v2_port = NodeConfig::port($cfg);
        $security = NodeConfig::security($cfg);
        $transport = (string) ($cfg['network'] ?? 'tcp');
        $host = NodeConfig::host($cfg, (string) $node_raw->server);
        $path = NodeConfig::path($cfg);
        $headers = $cfg['header']['request']['headers'] ?? [];
        $service_name = $cfg['servicename'] ?? '';
        $meek_url = $cfg['meek_url'] ?? '';
        $isVless = NodeConfig::isVless($cfg);
        $isReality = NodeConfig::isReality($cfg);

        $node = [
            'protocol' => $isVless ? 'vless' : 'vmess',
            'settings' => [
                'address' => $node_raw->server,
                'port' => $v2_port,
                'uuid' => $user->uuid,
            ],
            'tag' => $node_raw->name,
            'streamSettings' => [
                'transport' => $transport,
                'transportSettings' => array_filter([
                    'ws' => array_filter([
                        'path' => $transport === 'ws' ? $path : '',
                        'header' => $headers,
                    ]),
                    'grpc' => array_filter([
                        'host' => $transport === 'grpc' ? $host : '',
                        'service_name' => $service_name,
                    ]),
                    'meek' => array_filter([
                        'url' => $meek_url,
                    ]),
                    'httpupgrade' => array_filter([
                        'path' => $transport === 'httpupgrade' ? $path : '',
                        'host' => $transport === 'httpupgrade' ? $host : '',
                    ]),
                ]),
                'security' => $isReality ? 'reality' : (($security === 'tls' || $security === 'xtls') ? 'tls' : $security),
                'securitySettings' => [],
            ],
        ];

        if ($isVless) {
            $flow = NodeConfig::flow($cfg);
            if ($flow !== '') {
                $node['settings']['flow'] = $flow;
            }
        }

        if ($isReality) {
            $reality = NodeConfig::realityClient($cfg);
            $node['streamSettings']['securitySettings']['reality'] = array_filter([
                'server_name' => $reality['server_name'] !== '' ? $reality['server_name'] : $host,
                'public_key' => $reality['public_key'],
                'short_id' => $reality['short_id'],
                'fingerprint' => $reality['fingerprint'],
            ]);
        } elseif ($security === 'tls' || $security === 'xtls' || $security === 'auto') {
            $node['streamSettings']['securitySettings']['tls'] = array_filter([
                'server_name' => $host,
                'allow_insecure' => NodeConfig::allowInsecure($cfg),
                'fingerprint' => NodeConfig::fingerprint($cfg),
            ]);
        }

        $node['streamSettings']['securitySettings'] = array_filter($node['streamSettings']['securitySettings']);

        return $node;
    }
}
