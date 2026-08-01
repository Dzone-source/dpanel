<?php

declare(strict_types=1);

namespace App\Services\Subscribe;

/**
 * Hiddify-app profile.
 *
 * ClashMi works on the same Trojan/XrayR nodes using Clash Meta YAML.
 * Hiddify-app accepts Clash Meta (UA: "like ClashMeta") and converts it in-core.
 * Serving the same body as /clash avoids the Hiddify-only failure path of
 * base64 trojan:// → sing-box (ALPN / headerType / TLS tricks).
 *
 * @see https://github.com/hiddify/hiddify-app/wiki/URL-Scheme
 */
final class Hiddify extends Base
{
    public function getContent($user): string
    {
        return (new Clash())->getContent($user);
    }
}
