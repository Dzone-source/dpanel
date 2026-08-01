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
use function parse_url;
use function preg_replace;
use function rtrim;
use function str_contains;
use function strtolower;
use function strtotime;
use function trim;
use const PHP_URL_HOST;

final class SubController extends BaseController
{
    /**
     * @throws ClientExceptionInterface
     * @throws GuzzleException
     * @throws RedisException
     * @throws TelegramSDKException
     */
    public function index($request, $response, $args): ResponseInterface
    {
        $err_msg = '订阅链接无效';
        $subtype = isset($args['subtype']) ? (string) $args['subtype'] : '';
        $subtype_list = [
            'json', 'clash', 'sip008', 'singbox', 'v2rayjson', 'sip002', 'ss', 'v2ray', 'trojan',
            'hiddify', 'general',
        ];

        if (! $_ENV['Subscribe']) {
            return ResponseHelper::error($response, $err_msg);
        }

        if (! $this->isValidSubHost($request->getHeaderLine('Host'))) {
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
        $ua = $this->antiXss->xss_clean($request->getHeaderLine('User-Agent'));

        // Auto profile for Hiddify-app (empty subtype / json / generic).
        if ($subtype === '' || $subtype === 'json') {
            if ($this->isHiddifyUserAgent($ua)) {
                $subtype = 'hiddify';
            } elseif ($subtype === '') {
                $subtype = 'general';
            }
        }

        if (! in_array($subtype, $subtype_list, true)) {
            return ResponseHelper::error($response, $err_msg);
        }

        $sub_info = Subscribe::getContent($user, $subtype);

        // Hiddify /v2ray share = text/plain base64. Sing-box JSON only for singbox/v2rayjson.
        $content_type = match ($subtype) {
            'clash' => 'application/yaml',
            'json', 'sip008', 'singbox', 'v2rayjson' => 'application/json',
            default => 'text/plain; charset=utf-8',
        };

        $expire = (int) strtotime((string) $user->class_expire);
        // Same userinfo for Hiddify + Clash Meta (Hiddify now serves Clash YAML).
        $sub_details = 'upload=' . (int) $user->u
            . '; download=' . (int) $user->d
            . '; total=' . (int) $user->transfer_enable
            . '; expire=' . $expire;

        $appName = (string) ($_ENV['appName'] ?? 'DPanel');
        $profileTitle = 'base64:' . base64_encode($appName);
        $sub_content_disposition = 'attachment; filename="' . $appName . '"';
        // Hours (Hiddify ProfileParser Duration(hours: N)). Keep 6h like Clash.
        $sub_profile_update_interval = '6';
        $sub_profile_web_page_url = rtrim((string) ($_ENV['baseUrl'] ?? ''), '/');
        $supportUrl = rtrim((string) ($_ENV['supportUrl'] ?? $_ENV['baseUrl'] ?? ''), '/');

        if (Config::obtain('subscribe_log')) {
            (new SubscribeLog())->add($user, $subtype, $ua);
        }

        // Hiddify-app / Clash Meta profile headers (URL Scheme wiki).
        $withProfileHeaders = in_array($subtype, ['clash', 'singbox', 'hiddify', 'general', 'v2ray'], true);

        if ($withProfileHeaders) {
            $response = $response
                ->withHeader('Subscription-Userinfo', $sub_details)
                ->withHeader('subscription-userinfo', $sub_details)
                ->withHeader('Content-Disposition', $sub_content_disposition)
                ->withHeader('Profile-Update-Interval', $sub_profile_update_interval)
                ->withHeader('profile-update-interval', $sub_profile_update_interval)
                ->withHeader('Profile-Web-Page-Url', $sub_profile_web_page_url)
                ->withHeader('profile-web-page-url', $sub_profile_web_page_url)
                ->withHeader('Profile-Title', $profileTitle)
                ->withHeader('profile-title', $profileTitle)
                ->withHeader('Content-Type', $content_type);

            // HiddifyPanel optional support-url (branding_site).
            if ($subtype === 'hiddify' && $supportUrl !== '') {
                $response = $response
                    ->withHeader('support-url', $supportUrl)
                    ->withHeader('Support-Url', $supportUrl);
            }

            return $response->write($sub_info);
        }

        return $response
            ->withHeader('Subscription-Userinfo', $sub_details)
            ->withHeader('Content-Type', $content_type)
            ->write($sub_info);
    }

    private function isHiddifyUserAgent(string $ua): bool
    {
        $uaLower = strtolower($ua);

        return str_contains($uaLower, 'hiddify')
            || str_contains($uaLower, 'hiddifynext')
            || str_contains($uaLower, 'hiddify-next')
            || str_contains($uaLower, 'hiddifyng');
    }

    /**
     * Flexible Host check so reverse-proxy / Docker / CDN Host works.
     * Accepts subUrl host, baseUrl host, or X-Forwarded-Host when present.
     */
    private function isValidSubHost(string $hostHeader): bool
    {
        $candidates = [];
        foreach ([
            $hostHeader,
            $_SERVER['HTTP_X_FORWARDED_HOST'] ?? '',
        ] as $raw) {
            $h = $this->normalizeHost((string) $raw);
            if ($h !== '') {
                $candidates[] = $h;
            }
        }

        $allowed = [];
        foreach ([
            (string) ($_ENV['subUrl'] ?? ''),
            (string) ($_ENV['baseUrl'] ?? ''),
        ] as $url) {
            $h = $this->hostFromUrl($url);
            if ($h !== '') {
                $allowed[] = $h;
            }
        }

        if ($candidates === [] || $allowed === []) {
            return false;
        }

        foreach ($candidates as $requestHost) {
            if (in_array($requestHost, $allowed, true)) {
                return true;
            }
        }

        return false;
    }

    private function hostFromUrl(string $url): string
    {
        $host = strtolower((string) (parse_url($url, PHP_URL_HOST) ?: ''));
        if ($host !== '') {
            return $host;
        }

        $host = strtolower(trim($url));
        $host = preg_replace('#^https?://#', '', $host) ?? $host;
        if (str_contains($host, '/')) {
            $host = explode('/', $host, 2)[0];
        }

        return $this->normalizeHost($host);
    }

    private function normalizeHost(string $hostHeader): string
    {
        $host = strtolower(trim($hostHeader));
        // X-Forwarded-Host may be a comma-separated list.
        if (str_contains($host, ',')) {
            $host = trim(explode(',', $host, 2)[0]);
        }
        if (str_contains($host, ':')) {
            $host = explode(':', $host, 2)[0];
        }

        return $host;
    }
}
