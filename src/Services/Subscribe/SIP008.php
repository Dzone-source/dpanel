<?php

declare(strict_types=1);

namespace App\Services\Subscribe;

use App\Models\Config;
use App\Services\Subscribe;
use function is_array;
use function json_decode;
use function json_encode;

final class SIP008 extends Base
{
    public function getContent($user): string
    {
        $nodes = [];
        //判断是否开启SS订阅
        if (! Config::obtain('enable_ss_sub')) {
            return '';
        }

        $nodes_raw = Subscribe::getUserNodes($user);

        foreach ($nodes_raw as $node_raw) {
            if ((int) $node_raw->sort !== 0) {
                continue;
            }

            $node_custom_config = json_decode($node_raw->custom_config, true);
            if (! is_array($node_custom_config)) {
                $node_custom_config = [];
            }

            $plugin = $node_custom_config['plugin'] ?? '';
            $plugin_option = $node_custom_config['plugin_option'] ?? '';
            $nodes[] = [
                'id' => $node_raw->id,
                'remarks' => $node_raw->name,
                'server' => $node_raw->server,
                'server_port' => (int) $user->port,
                'password' => $user->passwd,
                'method' => $user->method,
                'plugin' => $plugin,
                'plugin_opts' => $plugin_option,
            ];
        }

        return json_encode([
            'version' => 1,
            'servers' => $nodes,
            'bytes_used' => $user->u + $user->d,
            'bytes_remaining' => $user->transfer_enable - $user->u - $user->d,
        ]);
    }
}
