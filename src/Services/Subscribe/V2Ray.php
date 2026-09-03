<?php

declare(strict_types=1);

namespace App\Services\Subscribe;

use App\Models\Config;
use App\Services\Subscribe;
use function base64_encode;
use function http_build_query;
use function json_encode;
use function rawurlencode;
use const PHP_EOL;

final class V2Ray extends Base
{
    public function getContent($user): string
    {
        $links = '';
        if (! Config::obtain('enable_v2_sub')) {
            return $links;
        }

        $nodes_raw = Subscribe::getUserNodes($user);

        foreach ($nodes_raw as $node_raw) {
            $node_custom_config = NodeConfig::decode($node_raw->custom_config);

            if ((int) $node_raw->sort !== 11) {
                continue;
            }

            $v2_port = NodeConfig::port($node_custom_config);
            $security = NodeConfig::security($node_custom_config);
            $network = (string) ($node_custom_config['network'] ?? 'tcp');
            $header = $node_custom_config['header'] ?? ['type' => 'none'];
            $header_type = $header['type'] ?? '';
            $host = NodeConfig::host($node_custom_config);
            $path = NodeConfig::path($node_custom_config, '/');
            $isVless = NodeConfig::isVless($node_custom_config);

            if ($isVless) {
                $links .= $this->buildVlessUri($node_raw, $user, $node_custom_config, $v2_port, $security, $network, $host, $path) . PHP_EOL;
                continue;
            }

            $v2rayn_array = [
                'v' => '2',
                'ps' => $node_raw->name,
                'add' => $node_raw->server,
                'port' => $v2_port,
                'id' => $user->uuid,
                'aid' => 0,
                'net' => $network,
                'type' => $header_type,
                'host' => $host,
                'path' => $path,
                'tls' => $security === 'reality' ? 'reality' : ($security === 'tls' || $security === 'xtls' ? 'tls' : ''),
            ];

            if ($security === 'reality') {
                $reality = NodeConfig::realityClient($node_custom_config);
                $v2rayn_array['sni'] = $reality['server_name'];
                $v2rayn_array['fp'] = $reality['fingerprint'];
                $v2rayn_array['pbk'] = $reality['public_key'];
                $v2rayn_array['sid'] = $reality['short_id'];
            }

            $links .= 'vmess://' . base64_encode(json_encode($v2rayn_array)) . PHP_EOL;
        }

        return $links;
    }

    private function buildVlessUri(
        object $node_raw,
        object $user,
        array $cfg,
        int $port,
        string $security,
        string $network,
        string $host,
        string $path
    ): string {
        $query = [
            'encryption' => 'none',
            'type' => $network === '' ? 'tcp' : $network,
            'security' => $security === 'none' ? 'none' : $security,
        ];

        $flow = NodeConfig::flow($cfg);
        if ($flow !== '') {
            $query['flow'] = $flow;
        }

        if ($host !== '') {
            $query['host'] = $host;
            $query['sni'] = $host;
        }

        if ($path !== '' && $path !== '/') {
            $query['path'] = $path;
        }

        $service = $cfg['servicename'] ?? $cfg['serviceName'] ?? '';
        if ($service !== '') {
            $query['serviceName'] = $service;
        }

        if ($security === 'tls' || $security === 'xtls' || $security === 'reality') {
            $query['fp'] = NodeConfig::fingerprint($cfg);
            if (NodeConfig::allowInsecure($cfg)) {
                $query['allowInsecure'] = '1';
            }
        }

        if ($security === 'reality') {
            $reality = NodeConfig::realityClient($cfg);
            $query['security'] = 'reality';
            $query['pbk'] = $reality['public_key'];
            $query['sid'] = $reality['short_id'];
            $query['fp'] = $reality['fingerprint'];
            if ($reality['server_name'] !== '') {
                $query['sni'] = $reality['server_name'];
            }
        }

        $qs = http_build_query($query);

        return 'vless://' . $user->uuid . '@' . $node_raw->server . ':' . $port
            . '?' . $qs . '#' . rawurlencode((string) $node_raw->name);
    }
}
