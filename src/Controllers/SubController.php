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
use function in_array;
use function strtotime;

final class SubController extends BaseController
{
    private const SUBTYPE_LIST = ['json', 'clash', 'sip008', 'singbox', 'v2rayjson', 'sip002', 'ss', 'v2ray', 'trojan'];

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
        // Strip optional port from Host (e.g. co2.vn:443)
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
            return ResponseHelper::error($response, $err_msg);
        }

        $link = (new Link())->where('token', $token)->first();

        if ($link === null || ! $link->isValid()) {
            return ResponseHelper::error($response, $err_msg);
        }

        $user = $link->user();
        $sub_info = Subscribe::getContent($user, $subtype);

        $content_type = match ($subtype) {
            'clash' => 'application/yaml',
            'json', 'sip008', 'singbox', 'v2rayjson' => 'application/json',
            default => 'text/plain',
        };

        $sub_details = ' upload=' . $user->u
            . '; download=' . $user->d
            . '; total=' . $user->transfer_enable
            . '; expire=' . strtotime($user->class_expire);
        $sub_content_disposition = 'attachment; filename=' . $_ENV['appName'];
        $sub_profile_update_interval = 6;
        $sub_profile_web_page_url = $_ENV['baseUrl'];
        $profile_title = (string) ($_ENV['appName'] ?? 'DPanel');

        if (Config::obtain('subscribe_log')) {
            (new SubscribeLog())->add(
                $user,
                $subtype,
                $this->antiXss->xss_clean($request->getHeaderLine('User-Agent'))
            );
        }

        return $response->withHeader('Subscription-Userinfo', $sub_details)
            ->withHeader('Content-Disposition', $sub_content_disposition)
            ->withHeader('Profile-Update-Interval', (string) $sub_profile_update_interval)
            ->withHeader('Profile-Web-Page-Url', $sub_profile_web_page_url)
            ->withHeader('Profile-Title', $profile_title)
            ->withHeader('Content-Type', $content_type)
            ->write($sub_info);
    }

    /**
     * Pick a subscription format when the client uses the bare /sub/{token} URL.
     */
    private function detectSubtype(string $userAgent): string
    {
        $ua = strtolower($userAgent);

        if ($ua === '') {
            return 'clash';
        }

        if (str_contains($ua, 'clash') ||
            str_contains($ua, 'stash') ||
            str_contains($ua, 'verge') ||
            str_contains($ua, 'flclash') ||
            str_contains($ua, 'nyanpasu') ||
            str_contains($ua, 'mihomo')
        ) {
            return 'clash';
        }

        // Hiddify (Flutter) often sends Dart/* without "hiddify" in UA.
        if (str_contains($ua, 'hiddify') ||
            str_contains($ua, 'sing-box') ||
            str_contains($ua, 'singbox') ||
            str_contains($ua, 'sfa') ||
            str_contains($ua, 'sfm') ||
            str_contains($ua, 'sfi') ||
            str_contains($ua, 'dart/') ||
            str_contains($ua, 'dio')
        ) {
            return 'singbox';
        }

        if (str_contains($ua, 'v2ray') ||
            str_contains($ua, 'v2box') ||
            str_contains($ua, 'shadowrocket') ||
            str_contains($ua, 'quantumult') ||
            str_contains($ua, 'surge') ||
            str_contains($ua, 'loon')
        ) {
            return 'v2ray';
        }

        // Most GUI clients accept Clash YAML from a bare subscription URL.
        return 'clash';
    }
}
