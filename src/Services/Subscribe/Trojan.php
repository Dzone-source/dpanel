<?php

declare(strict_types=1);

namespace App\Services\Subscribe;

use App\Models\Config;
use App\Services\Subscribe;
use function filter_var;
use function http_build_query;
use function json_decode;
use function rawurlencode;
use const FILTER_VALIDATE_BOOLEAN;
use const PHP_EOL;
use const PHP_QUERY_RFC3986;

final class Trojan extends Base
{
    public function getContent($user): string
    {
        $links = '';
        // Dedicated /trojan endpoint respects the admin toggle; General may still call us.
        // Keep generating when the toggle is on (default for this deployment).
        if (! Config::obtain('enable_trojan_sub')) {
            return $links;
        }

        $nodes_raw = Subscribe::getUserNodes($user);

        foreach ($nodes_raw as $node_raw) {
            $node_custom_config = json_decode($node_raw->custom_config, true);

            if ((int) $node_raw->sort === 14) {
                $trojan_port = $node_custom_config['offset_port_user'] ?? ($node_custom_config['offset_port_node'] ?? 443);
                $host = $node_custom_config['host'] ?? '';
                $allow_insecure = $node_custom_config['allow_insecure'] ?? '0';
                $security = $node_custom_config['security'] ?? 'tls';
                // Trojan on 443 always needs TLS — panel "none" / "0" breaks Hiddify share links.
                if ($security === '' || $security === 'none' || $security === '0') {
                    $security = 'tls';
                }
                $mux = $node_custom_config['mux'] ?? '0';
                $network = $node_custom_config['network'] ?? 'tcp';
                if ($network === '' || $network === 'none') {
                    $network = 'tcp';
                }
                $transport_plugin = $node_custom_config['transport_plugin'] ?? '';
                $transport_method = $node_custom_config['transport_method'] ?? '';
                $servicename = $node_custom_config['servicename'] ?? '';
                $path = $node_custom_config['path'] ?? '';

                $insecure = '1';
                // Hiddify shared.py: tcp → http/1.1; grpc/h2 → h2
                $alpn = ($network === 'grpc' || $network === 'h2') ? 'h2' : 'http/1.1';
                $query = http_build_query([
                    'peer' => $host,
                    'sni' => $host,
                    'allowInsecure' => $insecure,
                    'type' => $network,
                    'security' => $security !== '' ? $security : 'tls',
                    'fp' => 'chrome',
                    'alpn' => $alpn,
                ], '', '&', PHP_QUERY_RFC3986);

                if ($path !== '') {
                    $query .= '&path=' . rawurlencode($path);
                }
                if ($servicename !== '') {
                    $query .= '&serviceName=' . rawurlencode($servicename);
                }
                if ($transport_plugin !== '') {
                    $query .= '&obfs=' . rawurlencode($transport_plugin);
                }
                if ($transport_method !== '') {
                    $query .= '&obfsParam=' . rawurlencode($transport_method);
                }
                if ((string) $mux !== '' && (string) $mux !== '0') {
                    $query .= '&mux=' . rawurlencode((string) $mux);
                }

                $links .= 'trojan://' . $user->uuid . '@' . $node_raw->server . ':' . $trojan_port
                    . '?' . $query . '#' . rawurlencode((string) $node_raw->name) . PHP_EOL;
            }
        }

        return $links;
    }
}
