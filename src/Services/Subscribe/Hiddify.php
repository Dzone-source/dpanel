<?php

declare(strict_types=1);

namespace App\Services\Subscribe;

use App\Services\Subscribe;
use function base64_encode;
use function http_build_query;
use function implode;
use function rawurlencode;
use function rtrim;
use function strtotime;
use const PHP_EOL;

/**
 * Dedicated Hiddify-app profile (Hiddify-Manager / hiddify-app URL Scheme).
 *
 * Body = base64(all share links) — the format Hiddify imports most reliably
 * (v2ray subscription). Avoids full Sing-box JSON which often fails validation
 * in Hiddify's bundled core when rule_set / geosite is present.
 *
 * @see https://github.com/hiddify/hiddify-app/wiki/URL-Scheme
 * @see https://github.com/hiddify/Hiddify-Manager
 */
final class Hiddify extends Base
{
    public function getContent($user): string
    {
        $links = $this->collectLinks($user);

        if ($links === '') {
            return '';
        }

        return base64_encode($links);
    }

    /**
     * Plain-text profile with # header comments (when HTTP headers cannot be set).
     * Useful for debugging; primary delivery still uses HTTP headers + base64 body.
     */
    public function getPlainProfile($user): string
    {
        $title = (string) ($_ENV['appName'] ?? 'DPanel');
        $expire = (int) strtotime((string) $user->class_expire);
        $userinfo = 'upload=' . (int) $user->u
            . '; download=' . (int) $user->d
            . '; total=' . (int) $user->transfer_enable
            . '; expire=' . $expire;

        $header = [
            '#profile-title: base64:' . base64_encode($title),
            '#profile-update-interval: 6',
            '#subscription-userinfo: ' . $userinfo,
            '#profile-web-page-url: ' . rtrim((string) ($_ENV['baseUrl'] ?? ''), '/'),
        ];

        $links = $this->collectLinks($user);

        return implode(PHP_EOL, $header) . PHP_EOL . PHP_EOL . $links;
    }

    private function collectLinks($user): string
    {
        // Always include Trojan / V2 / SS share links for Hiddify (ignore per-format toggles
        // that empty the profile when admin disables a client type for Clash-only boards).
        $uri = '';

        $uri .= $this->forceTrojanLinks($user);
        $uri .= $this->forceV2Links($user);
        $uri .= (new SIP002())->getContent($user);

        return $uri;
    }

    private function forceTrojanLinks($user): string
    {
        // Trojan::getContent respects enable_trojan_sub; call builder path via reflection-free reimplementation.
        $links = '';
        $nodes_raw = Subscribe::getUserNodes($user);

        foreach ($nodes_raw as $node_raw) {
            if ((int) $node_raw->sort !== 14) {
                continue;
            }

            $cfg = NodeConfig::decode($node_raw->custom_config);
            $password = NodeConfig::trojanPassword($user);
            if ($password === '') {
                continue;
            }

            // Match HiddifyPanel xray.to_link (hiddify=1, alpn, headerType, host…).
            $query = NodeConfig::trojanShareQuery($cfg, (string) $node_raw->server);
            $port = NodeConfig::port($cfg);

            $links .= 'trojan://' . rawurlencode($password) . '@' . $node_raw->server . ':' . $port
                . '?' . http_build_query($query) . '#' . rawurlencode((string) $node_raw->name) . PHP_EOL;
        }

        return $links;
    }

    private function forceV2Links($user): string
    {
        // Reuse V2Ray share builder (respects enable_v2_sub — OK for VMess/VLESS nodes).
        return (new V2Ray())->getContent($user);
    }
}
