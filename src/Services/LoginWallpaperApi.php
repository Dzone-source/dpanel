<?php

declare(strict_types=1);

namespace App\Services;

use GuzzleHttp\Client;
use Throwable;
use function is_array;
use function json_decode;
use function rawurlencode;
use function trim;
use const BASE_PATH;

final class LoginWallpaperApi
{
    /**
     * @param callable(string, array): ?string|null $httpGet
     *
     * @return list<array{id: int, url: string, page_url: string, credit: string}>
     */
    public static function fetch(?callable $httpGet = null, ?string $cacheFile = null): array
    {
        $key = trim((string) ($_ENV['pexels_api_key'] ?? ''));
        if ($key === '') {
            return [];
        }

        $cacheFile ??= BASE_PATH . '/storage/framework/cache/pexels_4k_wallpaper.json';
        $cached = LoginWallpaperCache::read($cacheFile);
        if ($cached !== []) {
            return $cached;
        }

        $body = self::request($key, $httpGet);
        $photos = self::parse((string) $body);
        if ($photos !== []) {
            LoginWallpaperCache::write($cacheFile, $photos);
        }

        return $photos;
    }

    /**
     * @return list<array{id: int, url: string, page_url: string, credit: string}>
     */
    public static function parse(string $body): array
    {
        $decoded = json_decode($body, true);
        if (! is_array($decoded) || ! isset($decoded['photos']) || ! is_array($decoded['photos'])) {
            return [];
        }

        $photos = [];
        foreach ($decoded['photos'] as $photo) {
            $parsed = self::parsePhoto($photo);
            if ($parsed !== null) {
                $photos[] = $parsed;
            }
        }

        return $photos;
    }

    /**
     * @param callable(string, array): ?string|null $httpGet
     */
    private static function request(string $key, ?callable $httpGet): ?string
    {
        $httpGet ??= self::defaultHttpGet(...);
        $url = 'https://api.pexels.com/v1/search?query=' . rawurlencode(LoginWallpaper::SEARCH_QUERY)
            . '&orientation=landscape&size=large&per_page=80';

        try {
            return $httpGet($url, [
                'Authorization' => $key,
                'Accept' => 'application/json',
            ]);
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * @return array{id: int, url: string, page_url: string, credit: string}|null
     */
    private static function parsePhoto(mixed $photo): ?array
    {
        if (! is_array($photo)) {
            return null;
        }

        $id = (int) ($photo['id'] ?? 0);
        if ($id <= 0) {
            return null;
        }

        return LoginWallpaper::normalize(
            $id,
            (string) ($photo['url'] ?? ''),
            (string) ($photo['photographer'] ?? 'Pexels'),
        );
    }

    /**
     * @param array<string, string> $headers
     */
    private static function defaultHttpGet(string $url, array $headers): ?string
    {
        $client = new Client(['timeout' => 8, 'http_errors' => false]);
        $response = $client->get($url, ['headers' => $headers]);
        if ($response->getStatusCode() >= 400) {
            return null;
        }

        return $response->getBody()->getContents();
    }
}
