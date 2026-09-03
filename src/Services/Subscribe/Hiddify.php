<?php

declare(strict_types=1);

namespace App\Services\Subscribe;

use App\Services\Subscribe;
use function base64_encode;
use function http_build_query;
use function rawurlencode;
use const PHP_EOL;

/**
 * Hiddify-app profile (URL Scheme / remote subscription).
 *
 * Body = base64(share links). Hiddify-app validateConfig rejects DPanel Clash YAML
 * on add-profile (php yaml_emit ---/... + complex proxy-groups). ClashMi keeps using
 * /clash; Hiddify must stay on v2ray-style subscription that the core accepts.
 *
 * Trojan TCP query omits alpn (Clash Meta also omits it) to avoid Hiddify-only
 * mid-upload TLS issues on XrayR nodes.
 *
 * @see https://github.com/hiddify/hiddify-app/wiki/URL-Scheme
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

    private function collectLinks($user): string
    {
        $uri = '';
        $uri .= $this->forceTrojanLinks($user);
        $uri .= (new V2Ray())->getContent($user);
        $uri .= (new SIP002())->getContent($user);

        return $uri;
    }

    private function forceTrojanLinks($user): string
    {
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

            $query = NodeConfig::trojanShareQuery($cfg, (string) $node_raw->server);
            $port = NodeConfig::port($cfg);

            $links .= 'trojan://' . rawurlencode($password) . '@' . $node_raw->server . ':' . $port
                . '?' . http_build_query($query) . '#' . rawurlencode((string) $node_raw->name) . PHP_EOL;
        }

        return $links;
    }
}
