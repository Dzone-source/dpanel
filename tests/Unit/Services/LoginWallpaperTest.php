<?php

declare(strict_types=1);

use App\Services\LoginWallpaper;

beforeEach(function () {
    $this->originalEnv = $_ENV;
    $this->cacheFile = sys_get_temp_dir() . '/dpanel-pexels-wallpaper-' . uniqid('', true) . '.json';
});

afterEach(function () {
    $_ENV = $this->originalEnv;
    if (is_file($this->cacheFile)) {
        unlink($this->cacheFile);
    }
});

describe('LoginWallpaper::imageUrl', function () {
    it('builds a Pexels CDN url for the photo id', function () {
        expect(LoginWallpaper::imageUrl(1366919))
            ->toBe('https://images.pexels.com/photos/1366919/pexels-photo-1366919.jpeg?auto=compress&cs=tinysrgb&w=1920')
            ->and(LoginWallpaper::imageUrl(933054, 2560))
            ->toContain('/photos/933054/pexels-photo-933054.jpeg')
            ->toContain('w=2560');
    });
});

describe('LoginWallpaper::fallbackIds', function () {
    it('loads the 4k wallpaper photo pool from the bundled json', function () {
        $ids = LoginWallpaper::fallbackIds();

        expect($ids)
            ->toBeArray()
            ->toContain(LoginWallpaper::FALLBACK_ID)
            ->and(count($ids))->toBeGreaterThan(20);

        foreach ($ids as $id) {
            expect($id)->toBeInt()->toBeGreaterThan(0);
        }
    });

    it('falls back to a single id when the json file is missing', function () {
        expect(LoginWallpaper::fallbackIds('/tmp/does-not-exist-pexels.json'))
            ->toBe([LoginWallpaper::FALLBACK_ID]);
    });
});

describe('LoginWallpaper::random', function () {
    it('returns a wallpaper from the Pexels 4k wallpaper pool when no api key is set', function () {
        $_ENV['pexels_api_key'] = '';

        $wallpaper = LoginWallpaper::random();
        $ids = LoginWallpaper::fallbackIds();

        expect($wallpaper['id'])->toBeIn($ids)
            ->and($wallpaper['url'])->toBe(LoginWallpaper::imageUrl($wallpaper['id']))
            ->and($wallpaper['url'])->toStartWith('https://images.pexels.com/photos/')
            ->and($wallpaper['page_url'])->toContain('pexels.com')
            ->and($wallpaper['credit'])->toBe('Pexels');
    });

    it('uses live Pexels search results when the api key is configured', function () {
        $_ENV['pexels_api_key'] = 'test-pexels-key';

        $httpGet = static function (string $url, array $headers): string {
            expect($url)
                ->toContain('https://api.pexels.com/v1/search?')
                ->toContain(rawurlencode(LoginWallpaper::SEARCH_QUERY))
                ->toContain('orientation=landscape')
                ->and($headers['Authorization'])->toBe('test-pexels-key');

            return json_encode([
                'photos' => [
                    [
                        'id' => 424242,
                        'url' => 'https://www.pexels.com/photo/demo-424242/',
                        'photographer' => 'Ada Lovelace',
                    ],
                ],
            ], JSON_THROW_ON_ERROR);
        };

        $wallpaper = LoginWallpaper::random($httpGet, $this->cacheFile);

        expect($wallpaper['id'])->toBe(424242)
            ->and($wallpaper['url'])->toBe(LoginWallpaper::imageUrl(424242))
            ->and($wallpaper['page_url'])->toBe('https://www.pexels.com/photo/demo-424242/')
            ->and($wallpaper['credit'])->toBe('Ada Lovelace')
            ->and(is_file($this->cacheFile))->toBeTrue();
    });

    it('ignores a failed api response and uses the local 4k wallpaper pool', function () {
        $_ENV['pexels_api_key'] = 'bad-key';

        $httpGet = static function (): string {
            return '{"error":"Unauthorized"}';
        };

        $wallpaper = LoginWallpaper::random($httpGet, $this->cacheFile);

        expect($wallpaper['id'])->toBeIn(LoginWallpaper::fallbackIds())
            ->and(is_file($this->cacheFile))->toBeFalse();
    });
});

describe('LoginWallpaper::parseApiResponse', function () {
    it('skips photos without a valid id', function () {
        $photos = LoginWallpaper::parseApiResponse(json_encode([
            'photos' => [
                ['id' => 0],
                ['photographer' => 'Nobody'],
                ['id' => 1001, 'url' => 'https://www.pexels.com/photo/1001/', 'photographer' => 'N'],
            ],
        ], JSON_THROW_ON_ERROR));

        expect($photos)->toHaveCount(1)
            ->and($photos[0]['id'])->toBe(1001)
            ->and($photos[0]['credit'])->toBe('N');
    });
});
