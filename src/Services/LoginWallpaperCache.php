<?php

declare(strict_types=1);

namespace App\Services;

use function dirname;
use function file_get_contents;
use function file_put_contents;
use function filemtime;
use function is_array;
use function is_dir;
use function is_readable;
use function json_decode;
use function json_encode;
use function mkdir;
use function time;

final class LoginWallpaperCache
{
    /**
     * @return list<array{id: int, url: string, page_url: string, credit: string}>
     */
    public static function read(string $cacheFile): array
    {
        if (! is_readable($cacheFile)) {
            return [];
        }

        $mtime = filemtime($cacheFile);
        if ($mtime === false || $mtime < time() - 43200) {
            return [];
        }

        $decoded = json_decode((string) file_get_contents($cacheFile), true);
        if (! is_array($decoded)) {
            return [];
        }

        return $decoded;
    }

    /**
     * @param list<array{id: int, url: string, page_url: string, credit: string}> $photos
     */
    public static function write(string $cacheFile, array $photos): void
    {
        $dir = dirname($cacheFile);
        if (! is_dir($dir) && ! mkdir($dir, 0777, true) && ! is_dir($dir)) {
            return;
        }

        file_put_contents($cacheFile, json_encode($photos));
    }
}
