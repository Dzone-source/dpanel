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
            $query = NodeConfig::trojanShareQuery($cfg, (string) $node_raw->server);

            // Legacy SSPanel transport_plugin fields (not used by HiddifyPanel).
            $transport_plugin = (string) ($cfg['transport_plugin'] ?? '');
            $transport_method = (string) ($cfg['transport_method'] ?? '');
            if ($transport_plugin !== '') {
                $query['obfs'] = $transport_plugin;
            }
            if ($transport_method !== '') {
                $query['obfsParam'] = $transport_method;
            }

            $links .= 'trojan://' . rawurlencode($password) . '@' . $node_raw->server . ':' . $trojan_port
                . '?' . http_build_query($query) . '#' . rawurlencode((string) $node_raw->name) . PHP_EOL;
        }

        return $links;
    }
}
