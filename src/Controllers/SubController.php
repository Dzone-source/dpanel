<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\Config;
use App\Models\Link;
use App\Models\SubscribeLog;
use App\Services\RateLimit;
use App\Services\Subscribe;
use App\Utils\ResponseHelper;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Message\ResponseInterface;
use RedisException;
use Telegram\Bot\Exceptions\TelegramSDKException;
use function base64_encode;
use function in_array;
use function strtotime;

/**
 * Subscription endpoint — UA detection aligned with Xboard ProtocolManager.
 */
final class SubController extends BaseController
{
    private const SUBTYPE_LIST = [
        'json', 'clash', 'sip008', 'singbox', 'v2rayjson',
        'sip002', 'ss', 'v2ray', 'trojan', 'general',
    ];

    /**
     * @throws ClientExceptionInterface
     * @throws GuzzleException
     * @throws RedisException
     * @throws TelegramSDKException
     */
    public function index($request, $response, $args): ResponseInterface
    {
        $err_msg = 'Liên kết đăng ký không hợp lệ';
        $subtype = isset($args['subtype']) ? strtolower(trim((string) $args['subtype'])) : '';

        if ($subtype === '') {
            $subtype = $this->detectSubtype($request->getHeaderLine('User-Agent'));
        }

        $request_host = strtolower(trim($request->getHeaderLine('Host')));
        if (str_contains($request_host, ':')) {
            $request_host = explode(':', $request_host, 2)[0];
        }
        $configured_sub_host = strtolower((string) parse_url((string) $_ENV['subUrl'], PHP_URL_HOST));

        if (! $_ENV['Subscribe'] ||
            ! in_array($subtype, self::SUBTYPE_LIST, true) ||
            $configured_sub_host === '' ||
            $request_host !== $configured_sub_host
        ) {
            return ResponseHelper::error($response, $err_msg);
        }

        $token = $this->antiXss->xss_clean($args['token']);

        if ($_ENV['enable_rate_limit'] &&
            (! (new RateLimit())->checkRateLimit('sub_ip', $request->getServerParam('REMOTE_ADDR')) ||
            ! (new RateLimit())->checkRateLimit('sub_token', $token))
        ) {
            return ResponseHelper::error($response, 'Quá nhiều yêu cầu đăng ký, vui lòng thử lại sau', 429);
        }

        $link = (new Link())->where('token', $token)->first();

        if ($link === null || ! $link->isValid()) {
            return ResponseHelper::error($response, $err_msg);
        }

        $user = $link->user();
        $sub_info = Subscribe::getContent($user, $subtype);

        // Empty fallbacks (same idea as Xboard always having a usable body).
        if ($sub_info === '' && in_array($subtype, ['trojan', 'general', 'v2ray', 'singbox'], true)) {
            $subtype = $subtype === 'singbox' ? 'general' : 'clash';
            $sub_info = Subscribe::getContent($user, $subtype);
        }

        $content_type = match ($subtype) {
            'clash' => 'application/yaml',
            'json', 'sip008', 'singbox', 'v2rayjson' => 'application/json',
            default => 'text/plain',
        };

        $sub_details = ' upload=' . $user->u
            . '; download=' . $user->d
            . '; total=' . $user->transfer_enable
            . '; expire=' . strtotime($user->class_expire);
        $profile_title = (string) ($_ENV['appName'] ?? 'DPanel');
        // Xboard-compatible Profile-Title (emoji-safe)
        $profile_title_header = 'base64:' . base64_encode($profile_title);

        if (Config::obtain('subscribe_log')) {
            (new SubscribeLog())->add(
                $user,
                $subtype,
                $this->antiXss->xss_clean($request->getHeaderLine('User-Agent'))
            );
        }

        $response = $response->withHeader('Subscription-Userinfo', $sub_details)
            ->withHeader('Profile-Update-Interval', '24')
            ->withHeader('Profile-Web-Page-Url', (string) $_ENV['baseUrl'])
            ->withHeader('Profile-Title', $profile_title_header)
            ->withHeader('Content-Type', $content_type);

        if ($subtype === 'clash') {
            $response = $response->withHeader(
                'Content-Disposition',
                'attachment; filename=' . $profile_title
            );
        }

        return $response->write($sub_info);
    }

    /**
     * Mirror Xboard flag matching (longer / more specific flags win via order).
     */
    private function detectSubtype(string $userAgent): string
    {
        $ua = strtolower($userAgent);

        if ($ua === '') {
            return 'general';
        }

        // Clash Meta family
        if (str_contains($ua, 'clash') ||
            str_contains($ua, 'stash') ||
            str_contains($ua, 'verge') ||
            str_contains($ua, 'flclash') ||
            str_contains($ua, 'nyanpasu') ||
            str_contains($ua, 'mihomo') ||
            str_contains($ua, 'nekobox')
        ) {
            return 'clash';
        }

        // Hiddify on this board only accepts Clash YAML reliably (bare /sub + /clash).
        // Dart/Dio is Hiddify's Flutter HTTP client when the UA omits "hiddify".
        if (str_contains($ua, 'hiddify') ||
            str_contains($ua, 'dart/') ||
            str_contains($ua, 'dio')
        ) {
            return 'clash';
        }

        // Official sing-box apps
        if (str_contains($ua, 'sing-box') ||
            str_contains($ua, 'singbox') ||
            str_contains($ua, 'sfm') ||
            str_contains($ua, 'sfa') ||
            str_contains($ua, 'sfi')
        ) {
            return 'singbox';
        }

        if (str_contains($ua, 'v2ray') ||
            str_contains($ua, 'v2box') ||
            str_contains($ua, 'shadowrocket') ||
            str_contains($ua, 'quantumult') ||
            str_contains($ua, 'surge') ||
            str_contains($ua, 'loon') ||
            str_contains($ua, 'passwall') ||
            str_contains($ua, 'sagernet')
        ) {
            return 'general';
        }

        // Default bare link → Clash so clipboard import works in Hiddify / Clash Meta.
        return 'clash';
    }
}
