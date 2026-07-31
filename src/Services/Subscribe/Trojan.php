<?php

declare(strict_types=1);

namespace App\Services\Subscribe;

use App\Models\Config;
use App\Services\Subscribe;
use function http_build_query;
use function rawurlencode;
use const PHP_EOL;

final class Trojan extends Base
{
    public function getContent($user): string
    {
        $links = '';
        if (! Config::obtain('enable_trojan_sub')) {
            return $links;
        }

        $nodes_raw = Subscribe::getUserNodes($user);

        foreach ($nodes_raw as $node_raw) {
            $cfg = NodeConfig::decode($node_raw->custom_config);

            if ((int) $node_raw->sort !== 14) {
                continue;
            }

            $password = NodeConfig::trojanPassword($user);
            if ($password === '') {
                continue;
            }

            $trojan_port = NodeConfig::port($cfg);
            $host = NodeConfig::sni($cfg, (string) $node_raw->server);
            $allow_insecure = NodeConfig::allowInsecure($cfg) ? '1' : '0';
            $security = NodeConfig::isReality($cfg) ? 'reality' : ((string) ($cfg['security'] ?? 'tls'));
            $mux = NodeConfig::isTruthy($cfg['mux'] ?? false) ? '1' : '0';
            $network = (string) ($cfg['network'] ?? 'tcp');
            $transport_plugin = (string) ($cfg['transport_plugin'] ?? '');
            $transport_method = (string) ($cfg['transport_method'] ?? '');
            $servicename = (string) ($cfg['servicename'] ?? $cfg['serviceName'] ?? '');
            $path = NodeConfig::path($cfg);

            $query = [
                'peer' => $host,
                'sni' => $host,
                'allowInsecure' => $allow_insecure,
                'type' => $network === '' ? 'tcp' : $network,
                'security' => $security,
                'fp' => NodeConfig::fingerprint($cfg),
            ];

            if ($path !== '') {
                $query['path'] = $path;
            }
            if ($servicename !== '') {
                $query['serviceName'] = $servicename;
            }
            if ($transport_plugin !== '') {
                $query['obfs'] = $transport_plugin;
            }
            if ($transport_method !== '') {
                $query['obfsParam'] = $transport_method;
            }
            if ($mux === '1') {
                $query['mux'] = '1';
            }

            if (NodeConfig::isReality($cfg)) {
                $reality = NodeConfig::realityClient($cfg);
                $query['security'] = 'reality';
                $query['pbk'] = $reality['public_key'];
                $query['sid'] = $reality['short_id'];
                $query['fp'] = $reality['fingerprint'];
                if ($reality['server_name'] !== '') {
                    $query['sni'] = $reality['server_name'];
                    $query['peer'] = $reality['server_name'];
                }
            }

            $links .= 'trojan://' . rawurlencode($password) . '@' . $node_raw->server . ':' . $trojan_port
                . '?' . http_build_query($query) . '#' . rawurlencode((string) $node_raw->name) . PHP_EOL;
        }

        return $links;
    }
}
