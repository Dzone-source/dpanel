<?php

declare(strict_types=1);

namespace App\Services\Subscribe;

use App\Services\Subscribe;
use App\Utils\Tools;
use function array_fill_keys;
use function array_filter;
use function array_key_exists;
use function array_merge;
use function array_values;
use function in_array;
use function is_array;
use function strcasecmp;
use function yaml_emit;
use const YAML_UTF8_ENCODING;

final class Clash extends Base
{
    public function getContent($user): string
    {
        $nodes = [];
        $node_names = [];
        $clash_config = $this->normalizeLocalPorts($_ENV['Clash_Config'] ?? []);
        $clash_group_indexes = $_ENV['Clash_Group_Indexes'];
        $clash_group_config = $_ENV['Clash_Group_Config'];
        $nodes_raw = Subscribe::getUserNodes($user);

        foreach ($nodes_raw as $node_raw) {
            $node_custom_config = NodeConfig::decode($node_raw->custom_config);

            switch ((int) $node_raw->sort) {
                case 0:
                    $plugin = $node_custom_config['plugin'] ?? '';
                    $plugin_option = $node_custom_config['plugin_option'] ?? null;
                    $udp = $node_custom_config['udp'] ?? true;

                    $node = [
                        'name' => $node_raw->name,
                        'type' => 'ss',
                        'server' => $node_raw->server,
                        'port' => (int) $user->port,
                        'password' => $user->passwd,
                        'cipher' => $user->method,
                        'udp' => (bool) $udp,
                        'plugin' => $plugin,
                        'plugin-opts' => $plugin_option,
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

                    $udp = $node_custom_config['udp'] ?? true;
                    $server_key = $node_custom_config['server_key'] ?? '';
                    $uot = $node_custom_config['uot'] ?? false;

                    $node = [
                        'name' => $node_raw->name,
                        'type' => 'ss',
                        'server' => $node_raw->server,
                        'port' => $ss_2022_port,
                        'password' => $server_key === '' ? $user_pk : $server_key . ':' . $user_pk,
                        'cipher' => $method,
                        'udp' => (bool) $udp,
                        'udp_over_tcp' => (bool) $uot,
                    ];

                    break;
                case 2:
                    $tuic_port = NodeConfig::port($node_custom_config);
                    $host = NodeConfig::host($node_custom_config);
                    $congestion_control = $node_custom_config['congestion_control'] ?? 'bbr';
                    $node = [
                        'name' => $node_raw->name,
                        'type' => 'tuic',
                        'server' => $node_raw->server,
                        'port' => $tuic_port,
                        'password' => $user->passwd,
                        'uuid' => $user->uuid,
                        'sni' => $host,
                        'congestion-controller' => $congestion_control,
                        'reduce-rtt' => true,
                    ];

                    break;
                case 11:
                    $node = $this->buildV2Family($node_raw, $user, $node_custom_config);
                    break;
                case 14:
                    $node = $this->buildTrojan($node_raw, $user, $node_custom_config);
                    break;
                case 12:
                    // VLESS (+ optional Reality) — Clash.Meta / mihomo
                    $vless_port = NodeConfig::port($node_custom_config);
                    $network = NodeConfig::network($node_custom_config, 'tcp');
                    if ($network === '' || $network === 'none') {
                        $network = 'tcp';
                    }
                    $host = NodeConfig::host($node_custom_config);
                    $path = NodeConfig::path($node_custom_config);
                    $security = NodeConfig::security($node_custom_config);
                    $flow = NodeConfig::flow($node_custom_config);
                    $allow_insecure = NodeConfig::allowInsecure($node_custom_config);
                    if (! $allow_insecure && $host !== '' && strcasecmp($host, (string) $node_raw->server) !== 0) {
                        $allow_insecure = true;
                    }
                    $udp = $node_custom_config['udp'] ?? true;
                    $service_name = NodeConfig::serviceName($node_custom_config);
                    $ws_opts = $node_custom_config['ws-opts'] ?? $node_custom_config['ws_opts'] ?? null;
                    $grpc_opts = $node_custom_config['grpc-opts'] ?? $node_custom_config['grpc_opts'] ?? null;
                    $http_opts = $node_custom_config['http-opts'] ?? $node_custom_config['http_opts'] ?? null;

                    if ($network === 'httpupgrade') {
                        $network = 'ws';
                        $ws_opts = array_merge($ws_opts ?? [], ['v2ray-http-upgrade' => true]);
                    }
                    if ($ws_opts === null && $network === 'ws') {
                        $ws_opts = array_filter([
                            'path' => $path !== '' ? $path : null,
                            'headers' => $host !== '' ? ['Host' => $host] : null,
                        ]);
                    }
                    if ($grpc_opts === null && $network === 'grpc' && $service_name !== '') {
                        $grpc_opts = ['grpc-service-name' => $service_name];
                    }

                    $node = [
                        'name' => $node_raw->name,
                        'type' => 'vless',
                        'server' => $node_raw->server,
                        'port' => $vless_port,
                        'uuid' => $user->uuid,
                        'udp' => (bool) $udp,
                        'network' => $network,
                        'tls' => in_array($security, ['tls', 'reality'], true),
                        'skip-cert-verify' => $allow_insecure,
                        'servername' => $host,
                        'client-fingerprint' => NodeConfig::fingerprint($node_custom_config),
                        'flow' => $flow !== '' ? $flow : null,
                        'ws-opts' => $ws_opts,
                        'grpc-opts' => $grpc_opts,
                        'http-opts' => $http_opts,
                    ];

                    if ($security === 'reality') {
                        $node['reality-opts'] = [
                            'public-key' => NodeConfig::publicKey($node_custom_config),
                            'short-id' => NodeConfig::shortId($node_custom_config),
                        ];
                    }

                    $node = array_filter($node, static fn ($v) => $v !== null && $v !== []);

                    break;
                case 13:
                    // Hysteria2 — Clash.Meta / mihomo
                    $hy_port = NodeConfig::port($node_custom_config);
                    $host = NodeConfig::host($node_custom_config);
                    $allow_insecure = NodeConfig::allowInsecure($node_custom_config);
                    $up = (int) ($node_custom_config['up_mbps'] ?? $node_custom_config['up'] ?? 100);
                    $down = (int) ($node_custom_config['down_mbps'] ?? $node_custom_config['down'] ?? 100);
                    $obfs = (string) ($node_custom_config['obfs'] ?? '');
                    $obfs_password = (string) ($node_custom_config['obfs_password'] ?? $node_custom_config['obfs-password'] ?? '');

                    $node = [
                        'name' => $node_raw->name,
                        'type' => 'hysteria2',
                        'server' => $node_raw->server,
                        'port' => $hy_port,
                        'password' => $user->uuid,
                        'sni' => $host,
                        'up' => $up,
                        'down' => $down,
                        'skip-cert-verify' => $allow_insecure,
                    ];

                    if (isset($node_custom_config['ports']) && $node_custom_config['ports'] !== '') {
                        $node['ports'] = (string) $node_custom_config['ports'];
                    }
                    if (isset($node_custom_config['hop_interval'])) {
                        $node['hop-interval'] = (int) $node_custom_config['hop_interval'];
                    }
                    if ($obfs !== '') {
                        $node['obfs'] = $obfs;
                        if ($obfs_password !== '') {
                            $node['obfs-password'] = $obfs_password;
                        }
                    }

                    break;
                case 15:
                    // AnyTLS — Clash.Meta / mihomo
                    $any_port = NodeConfig::port($node_custom_config);
                    $host = NodeConfig::host($node_custom_config);
                    $allow_insecure = NodeConfig::allowInsecure($node_custom_config);

                    $node = [
                        'name' => $node_raw->name,
                        'type' => 'anytls',
                        'server' => $node_raw->server,
                        'port' => $any_port,
                        'password' => $user->uuid,
                        'udp' => true,
                        'sni' => $host !== '' ? $host : null,
                        'skip-cert-verify' => $allow_insecure,
                    ];
                    $node = array_filter($node, static fn ($v) => $v !== null);

                    break;
                default:
                    $node = [];
                    break;
            }

            if ($node === []) {
                continue;
            }

            $nodes[] = $node;
            $node_names[] = (string) $node_raw->name;

            foreach ($clash_group_indexes as $index) {
                $clash_group_config['proxy-groups'][$index]['proxies'][] = $node_raw->name;
            }
        }

        $clash_group_config = $this->prioritizeNodesInSelectGroups($clash_group_config, $node_names);

        $clash_nodes = [
            'proxies' => $nodes,
        ];

        return yaml_emit(
            array_merge($clash_config, $clash_nodes, $clash_group_config),
            YAML_UTF8_ENCODING
        );
    }

    /**
     * ClashMi / Clash Meta already bind mixed-port (default 7890). Legacy profiles
     * that also emit port + socks-port cause a second HTTP listen on the same
     * address and fail with: listen tcp 127.0.0.1:7890: bind: Only one usage of
     * each socket address (protocol/network address/port) is normally permitted.
     * That restart loop looks like intermittent disconnects for ClashMi users.
     */
    private function normalizeLocalPorts(array $clash_config): array
    {
        if (! isset($clash_config['mixed-port'])) {
            $clash_config['mixed-port'] = isset($clash_config['port'])
                ? (int) $clash_config['port']
                : 7890;
        }

        unset($clash_config['port'], $clash_config['socks-port']);

        // Prefer IPv4 on mobile carriers with broken IPv6 paths.
        if (! array_key_exists('ipv6', $clash_config)) {
            $clash_config['ipv6'] = false;
        }

        return $clash_config;
    }

    /**
     * Put real node names first in select groups so ClashMi does not default to
     * url-test "Tự động chọn". Failed url-test probes on mobile 4G switch nodes and
     * look like intermittent disconnects.
     *
     * @param list<string> $nodeNames
     */
    private function prioritizeNodesInSelectGroups(array $groupConfig, array $nodeNames): array
    {
        if ($nodeNames === [] || ! isset($groupConfig['proxy-groups']) || ! is_array($groupConfig['proxy-groups'])) {
            return $groupConfig;
        }

        $nodeSet = array_fill_keys($nodeNames, true);

        foreach ($groupConfig['proxy-groups'] as &$group) {
            if (($group['type'] ?? '') !== 'select' || ! isset($group['proxies']) || ! is_array($group['proxies'])) {
                continue;
            }

            $nodes = [];
            $other = [];
            foreach ($group['proxies'] as $name) {
                $name = (string) $name;
                if (isset($nodeSet[$name])) {
                    $nodes[] = $name;
                } else {
                    $other[] = $name;
                }
            }

            $group['proxies'] = array_values(array_merge($nodes, $other));
        }
        unset($group);

        return $groupConfig;
    }

    /**
     * Build Clash Meta VMess / VLESS (+ TLS / REALITY) entry for sort=11 nodes.
     * Hiddify imports /clash and requires correct type + reality-opts when the node is VLESS.
     */
    private function buildV2Family(object $node_raw, object $user, array $cfg): array
    {
        $v2_port = NodeConfig::port($cfg);
        $security = NodeConfig::security($cfg);
        $encryption = $cfg['encryption'] ?? 'auto';
        $network = $cfg['network'] ?? 'tcp';
        if ($network === '' || $network === 'none') {
            $network = 'tcp';
        }
        $host = NodeConfig::host($cfg);
        $allow_insecure = NodeConfig::allowInsecure($cfg);
        $udp = $cfg['udp'] ?? true;
        $ws_opts = $cfg['ws-opts'] ?? $cfg['ws_opts'] ?? null;
        $h2_opts = $cfg['h2-opts'] ?? $cfg['h2_opts'] ?? null;
        $http_opts = $cfg['http-opts'] ?? $cfg['http_opts'] ?? null;
        $grpc_opts = $cfg['grpc-opts'] ?? $cfg['grpc_opts'] ?? null;
        $isVless = NodeConfig::isVless($cfg);
        $isReality = NodeConfig::isReality($cfg);

        if ($network === 'httpupgrade') {
            $network = 'ws';
        }

        // Build ws-opts from path/host when panel only stores flat fields (common XrayR custom_config).
        if ($ws_opts === null && ($network === 'ws' || ($cfg['network'] ?? '') === 'httpupgrade')) {
            $path = NodeConfig::path($cfg, '/');
            $ws_opts = [
                'path' => $path,
            ];
            if ($host !== '') {
                $ws_opts['headers'] = ['Host' => $host];
            }
            if (($cfg['network'] ?? '') === 'httpupgrade') {
                $ws_opts['v2ray-http-upgrade'] = true;
            }
        }

        if ($grpc_opts === null && $network === 'grpc') {
            $service = $cfg['servicename'] ?? $cfg['serviceName'] ?? '';
            if ($service !== '') {
                $grpc_opts = ['grpc-service-name' => $service];
            }
        }

        $node = [
            'name' => $node_raw->name,
            'type' => $isVless ? 'vless' : 'vmess',
            'server' => $node_raw->server,
            'port' => $v2_port,
            'uuid' => $user->uuid,
            'udp' => (bool) $udp,
            'network' => $network === '' ? 'tcp' : $network,
            'ws-opts' => $ws_opts,
            'h2-opts' => $h2_opts,
            'http-opts' => $http_opts,
            'grpc-opts' => $grpc_opts,
        ];

        if (! $isVless) {
            $node['alterId'] = 0;
            $node['cipher'] = $encryption;
        } else {
            $node['cipher'] = 'auto';
            $flow = NodeConfig::flow($cfg);
            if ($flow !== '') {
                $node['flow'] = $flow;
            }
        }

        if ($isReality) {
            $reality = NodeConfig::realityClient($cfg);
            $node['tls'] = true;
            $node['skip-cert-verify'] = $allow_insecure;
            $node['servername'] = $reality['server_name'] !== '' ? $reality['server_name'] : $host;
            $node['client-fingerprint'] = $reality['fingerprint'];
            $node['reality-opts'] = [
                'public-key' => $reality['public_key'],
                'short-id' => $reality['short_id'],
            ];
        } elseif ($security === 'tls' || $security === 'xtls') {
            $node['tls'] = true;
            // Fake-SNI: enable skip-verify when SNI host ≠ node server (common SoftBank unlock).
            if (! $allow_insecure && $host !== '' && strcasecmp($host, (string) $node_raw->server) !== 0) {
                $allow_insecure = true;
            }
            $node['skip-cert-verify'] = $allow_insecure;
            $node['servername'] = $host;
            $node['client-fingerprint'] = NodeConfig::fingerprint($cfg);
        } else {
            $node['tls'] = false;
        }

        return $node;
    }

    /**
     * Clash Meta Trojan for Hiddify. Builds ws/grpc opts from flat DPanel custom_config.
     */
    private function buildTrojan(object $node_raw, object $user, array $cfg): array
    {
        $password = NodeConfig::trojanPassword($user);
        if ($password === '') {
            return [];
        }

        // Prefer flat `network` (XrayR custom_config). Do NOT use header.type —
        // that field is HTTP camouflage (often "none"), not the transport name.
        $rawNetwork = (string) ($cfg['network'] ?? 'tcp');
        $network = $rawNetwork === '' ? 'tcp' : $rawNetwork;
        $sni = NodeConfig::sni($cfg, (string) $node_raw->server);
        $allow_insecure = NodeConfig::allowInsecure($cfg);
        // Fake-SNI / SoftBank unlock: cert host ≠ connect IP → ClashMi TLS fails mid-session.
        if (! $allow_insecure && $sni !== '' && strcasecmp($sni, (string) $node_raw->server) !== 0) {
            $allow_insecure = true;
        }
        $udp = $cfg['udp'] ?? true;
        $ws_opts = $cfg['ws-opts'] ?? $cfg['ws_opts'] ?? null;
        $grpc_opts = $cfg['grpc-opts'] ?? $cfg['grpc_opts'] ?? null;

        if ($network === 'httpupgrade') {
            $network = 'ws';
        }

        if ($ws_opts === null && ($network === 'ws' || $rawNetwork === 'httpupgrade')) {
            $path = NodeConfig::path($cfg, '/');
            $ws_opts = ['path' => $path];
            $hostHeader = NodeConfig::host($cfg);
            if ($hostHeader !== '') {
                $ws_opts['headers'] = ['Host' => $hostHeader];
            }
            if ($rawNetwork === 'httpupgrade') {
                $ws_opts['v2ray-http-upgrade'] = true;
            }
        }

        if ($grpc_opts === null && $network === 'grpc') {
            $service = $cfg['servicename'] ?? $cfg['serviceName'] ?? '';
            if ($service !== '') {
                $grpc_opts = ['grpc-service-name' => $service];
            }
        }

        $node = [
            'name' => $node_raw->name,
            'type' => 'trojan',
            'server' => $node_raw->server,
            'sni' => $sni,
            'port' => NodeConfig::port($cfg),
            'password' => $password,
            'udp' => (bool) $udp,
            'skip-cert-verify' => $allow_insecure,
            'client-fingerprint' => NodeConfig::fingerprint($cfg),
        ];

        if ($network !== '' && $network !== 'tcp') {
            $node['network'] = $network;
        }

        if ($ws_opts !== null) {
            $node['ws-opts'] = $ws_opts;
        }
        if ($grpc_opts !== null) {
            $node['grpc-opts'] = $grpc_opts;
        }

        if (NodeConfig::isReality($cfg)) {
            $reality = NodeConfig::realityClient($cfg);
            $node['sni'] = $reality['server_name'] !== '' ? $reality['server_name'] : $sni;
            $node['client-fingerprint'] = $reality['fingerprint'];
            $node['reality-opts'] = [
                'public-key' => $reality['public_key'],
                'short-id' => $reality['short_id'],
            ];
        }

        return $node;
    }
}
