<?php

declare(strict_types=1);

namespace App\Services\Subscribe;

use App\Models\Config;
use function base64_encode;

/**
 * Xboard-style "General" subscription: base64-encoded share links.
 * Safe fallback for Hiddify when UA is generic (Dart/Dio).
 */
final class General extends Base
{
    public function getContent($user): string
    {
        $uri = '';

        // Trojan share links (primary for this board)
        $uri .= (new Trojan())->getContent($user);

        if (Config::obtain('enable_v2_sub')) {
            $uri .= (new V2Ray())->getContent($user);
        }

        if (Config::obtain('enable_ss_sub')) {
            // SIP002 produces proper ss:// URLs (SS.php does not).
            $uri .= (new SIP002())->getContent($user);
        }

        if ($uri === '') {
            return '';
        }

        return base64_encode($uri);
    }
}
