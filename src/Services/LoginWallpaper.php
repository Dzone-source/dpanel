<?php

declare(strict_types=1);

namespace App\Services;

use function array_rand;

final class LoginWallpaper
{
    public const SEARCH_QUERY = '4k wallpaper';
    public const SEARCH_URL = 'https://www.pexels.com/search/4k%20wallpaper/';
    public const FALLBACK_ID = 1366919;

    /**
     * @param callable(string, array): ?string|null $httpGet
     *
     * @return array{id: int, url: string, page_url: string, credit: string}
     */
    public static function random(?callable $httpGet = null, ?string $cacheFile = null): array
    {
        $photos = self::photos($httpGet, $cacheFile);
        if ($photos === []) {
            return self::normalize(self::FALLBACK_ID);
        }

        return $photos[array_rand($photos)];
    }

    /**
     * @param callable(string, array): ?string|null $httpGet
     *
     * @return list<array{id: int, url: string, page_url: string, credit: string}>
     */
    public static function photos(?callable $httpGet = null, ?string $cacheFile = null): array
    {
        $fromApi = LoginWallpaperApi::fetch($httpGet, $cacheFile);
        if ($fromApi !== []) {
            return $fromApi;
        }

        return self::fallbackPhotos();
    }

    public static function imageUrl(int $id, int $width = 1920): string
    {
        return 'https://images.pexels.com/photos/' . $id . '/pexels-photo-' . $id
            . '.jpeg?auto=compress&cs=tinysrgb&w=' . $width;
    }

    /**
     * @return array{id: int, url: string, page_url: string, credit: string}
     */
    public static function normalize(int $id, string $pageUrl = '', string $credit = 'Pexels'): array
    {
        $page = $pageUrl !== '' ? $pageUrl : self::SEARCH_URL;
        $name = $credit !== '' ? $credit : 'Pexels';

        return [
            'id' => $id,
            'url' => self::imageUrl($id),
            'page_url' => $page,
            'credit' => $name,
        ];
    }

    /**
     * @return list<array{id: int, url: string, page_url: string, credit: string}>
     */
    public static function fallbackPhotos(?string $jsonPath = null): array
    {
        return LoginWallpaperCatalog::photos($jsonPath);
    }

    /**
     * @return list<int>
     */
    public static function fallbackIds(?string $jsonPath = null): array
    {
        return LoginWallpaperCatalog::ids($jsonPath);
    }

    /**
     * @return list<array{id: int, url: string, page_url: string, credit: string}>
     */
    public static function parseApiResponse(string $body): array
    {
        return LoginWallpaperApi::parse($body);
    }
}
