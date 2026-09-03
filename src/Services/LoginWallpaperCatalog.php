<?php

declare(strict_types=1);

namespace App\Services;

use function file_get_contents;
use function is_array;
use function is_readable;
use function json_decode;
use const BASE_PATH;

final class LoginWallpaperCatalog
{
    public static function path(?string $jsonPath = null): string
    {
        if ($jsonPath !== null) {
            return $jsonPath;
        }

        return BASE_PATH . '/public/assets/data/pexels-4k-wallpapers.json';
    }

    /**
     * @return list<int>
     */
    public static function ids(?string $jsonPath = null): array
    {
        $path = self::path($jsonPath);
        if (! is_readable($path)) {
            return [LoginWallpaper::FALLBACK_ID];
        }

        $decoded = json_decode((string) file_get_contents($path), true);
        $rawIds = [];
        if (is_array($decoded) && isset($decoded['ids']) && is_array($decoded['ids'])) {
            $rawIds = $decoded['ids'];
        }

        $ids = [];
        foreach ($rawIds as $id) {
            $id = (int) $id;
            if ($id > 0) {
                $ids[] = $id;
            }
        }

        if ($ids === []) {
            return [LoginWallpaper::FALLBACK_ID];
        }

        return $ids;
    }

    /**
     * @return list<array{id: int, url: string, page_url: string, credit: string}>
     */
    public static function photos(?string $jsonPath = null): array
    {
        $photos = [];
        foreach (self::ids($jsonPath) as $id) {
            $photos[] = LoginWallpaper::normalize($id);
        }

        return $photos;
    }
}
