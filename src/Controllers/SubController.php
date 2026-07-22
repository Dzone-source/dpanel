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

        if ($sub_info === '' && $subtype !== 'clash') {
            $subtype = 'clash';
            $sub_info = Subscribe::getContent($user, $subtype);
        }

        $content_type = match ($subtype) {
            'clash' => 'text/yaml; charset=UTF-8',
            'json', 'sip008', 'singbox', 'v2rayjson' => 'application/json; charset=UTF-8',
            default => 'text/plain; charset=UTF-8',
        };

        $sub_details = 'upload=' . $user->u
            . '; download=' . $user->d
            . '; total=' . $user->transfer_enable
            . '; expire=' . strtotime($user->class_expire);
        $profile_title = (string) ($_ENV['appName'] ?? 'DPanel');

        if (Config::obtain('subscribe_log')) {
            (new SubscribeLog())->add(
                $user,
                $subtype,
                $this->antiXss->xss_clean($request->getHeaderLine('User-Agent'))
            );
        }

        // Keep headers minimal — Content-Disposition / base64 Profile-Title break some Hiddify builds.
        // no-store so Hiddify/Clash do not keep a stale SingBox/Clash profile after panel fixes.
        return $response
            ->withHeader('Subscription-Userinfo', $sub_details)
            ->withHeader('Profile-Update-Interval', '1')
            ->withHeader('Profile-Web-Page-Url', (string) $_ENV['baseUrl'])
            ->withHeader('Profile-Title', $profile_title)
            ->withHeader('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
            ->withHeader('Pragma', 'no-cache')
            ->withHeader('Expires', '0')
            ->withHeader('Content-Type', $content_type)
            ->write($sub_info);
    }

    private function detectSubtype(string $userAgent): string
    {
        $ua = strtolower($userAgent);

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

        // Hiddify + official sing-box apps — same simplified /singbox body that SFA accepts.
        if (str_contains($ua, 'hiddify') ||
            str_contains($ua, 'dart/') ||
            str_contains($ua, 'dio') ||
            str_contains($ua, 'sing-box') ||
            str_contains($ua, 'singbox') ||
            str_contains($ua, 'sfm') ||
            str_contains($ua, 'sfa') ||
            str_contains($ua, 'sfi')
        ) {
            return 'singbox';
        }

        return 'clash';
    }
}
