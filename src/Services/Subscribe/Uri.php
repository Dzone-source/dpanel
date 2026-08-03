<?php

declare(strict_types=1);

namespace App\Services\Subscribe;

use App\Services\Subscribe;
use function http_build_query;
use function json_decode;
use function rawurlencode;
use const PHP_EOL;

/**
 * Mixed share-link subscription (vless / hysteria2 / anytls / trojan / vmess),
 * adapted from Xboard General protocol builders to SSPanel custom_config fields.
 */
final class Uri extends Base
{
    public function getContent($user): string
    {
        $links = '';
        $nodes_raw = Subscribe::getUserNodes($user);

        foreach ($nodes_raw as $node_raw) {
            $cfg = json_decode($node_raw->custom_config, true) ?: [];

            $links .= match ((int) $node_raw->sort) {
                12 => $this->buildVless($user->uuid, $node_raw->name, $node_raw->server, $cfg),
                13 => $this->buildHysteria2($user->uuid, $node_raw->name, $node_raw->server, $cfg),
                15 => $this->buildAnytls($user->uuid, $node_raw->name, $node_raw->server, $cfg),
                14 => $this->buildTrojan($user->uuid, $node_raw->name, $node_raw->server, $cfg),
                default => '',
            };
        }

        return $links;
    }

    private function buildVless(string $uuid, string $name, string $server, array $cfg): string
    {
        $port = NodeConfigHelper::port($cfg);
        $security = NodeConfigHelper::security($cfg);
        $network = NodeConfigHelper::network($cfg, 'tcp');
        $host = NodeConfigHelper::host($cfg, $server);
        $path = NodeConfigHelper::path($cfg);

        $query = [
            'encryption' => 'none',
            'type' => $network,
            'security' => $security === 'none' ? 'none' : $security,
        ];

        if ($flow = NodeConfigHelper::flow($cfg)) {
            $query['flow'] = $flow;
        }

        if ($security === 'tls') {
            $query['sni'] = $host;
            $query['fp'] = NodeConfigHelper::fingerprint($cfg);
            if (NodeConfigHelper::allowInsecure($cfg)) {
                $query['allowInsecure'] = '1';
            }
        } elseif ($security === 'reality') {
            $query['sni'] = $host;
            $query['fp'] = NodeConfigHelper::fingerprint($cfg);
            $query['pbk'] = NodeConfigHelper::publicKey($cfg);
            $query['sid'] = NodeConfigHelper::shortId($cfg);
            $query['spx'] = '/';
        }

        if ($network === 'ws') {
            $query['path'] = $path !== '' ? $path : '/';
            $query['host'] = $host;
        } elseif ($network === 'grpc') {
            $query['serviceName'] = NodeConfigHelper::serviceName($cfg);
        } elseif ($network === 'httpupgrade' || $network === 'xhttp') {
            $query['path'] = $path !== '' ? $path : '/';
            $query['host'] = $host;
        }

        return 'vless://' . $uuid . '@' . $server . ':' . $port . '?' .
            http_build_query($query) . '#' . rawurlencode($name) . PHP_EOL;
    }

    private function buildHysteria2(string $password, string $name, string $server, array $cfg): string
    {
        $port = NodeConfigHelper::port($cfg);
        $host = NodeConfigHelper::host($cfg);
        $query = [];

        if ($host !== '') {
            $query['sni'] = $host;
        }
        if (NodeConfigHelper::allowInsecure($cfg)) {
            $query['insecure'] = '1';
        }

        $obfs = (string) ($cfg['obfs'] ?? '');
        $obfs_password = (string) ($cfg['obfs_password'] ?? $cfg['obfs-password'] ?? '');
        if ($obfs !== '') {
            $query['obfs'] = $obfs;
            if ($obfs_password !== '') {
                $query['obfs-password'] = $obfs_password;
            }
        }

        $qs = $query === [] ? '' : ('?' . http_build_query($query));

        return 'hysteria2://' . rawurlencode($password) . '@' . $server . ':' . $port .
            $qs . '#' . rawurlencode($name) . PHP_EOL;
    }

    private function buildAnytls(string $password, string $name, string $server, array $cfg): string
    {
        $port = NodeConfigHelper::port($cfg);
        $host = NodeConfigHelper::host($cfg);
        $query = [];

        if ($host !== '') {
            $query['sni'] = $host;
        }
        if (NodeConfigHelper::allowInsecure($cfg)) {
            $query['insecure'] = '1';
        }

        $qs = $query === [] ? '' : ('?' . http_build_query($query));

        return 'anytls://' . rawurlencode($password) . '@' . $server . ':' . $port .
            $qs . '#' . rawurlencode($name) . PHP_EOL;
    }

    private function buildTrojan(string $password, string $name, string $server, array $cfg): string
    {
        $port = NodeConfigHelper::port($cfg);
        $host = NodeConfigHelper::host($cfg);
        $query = [
            'peer' => $host,
            'sni' => $host,
            'allowInsecure' => NodeConfigHelper::allowInsecure($cfg) ? '1' : '0',
            'type' => NodeConfigHelper::network($cfg, 'tcp'),
            'security' => NodeConfigHelper::security($cfg) === 'none' ? 'tls' : NodeConfigHelper::security($cfg),
        ];

        return 'trojan://' . $password . '@' . $server . ':' . $port . '?' .
            http_build_query($query) . '#' . rawurlencode($name) . PHP_EOL;
    }
}
