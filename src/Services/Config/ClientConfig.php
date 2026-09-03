<?php

declare(strict_types=1);

namespace App\Services\Config;

final class ClientConfig
{
    private static ?array $config = null;

    public static function getClients(string $sub, string $name, bool $r2): array
    {
        if (self::$config === null) {
            $file = BASE_PATH . '/config/client_display.json';
            if (! is_readable($file)) {
                throw new \RuntimeException("Client config file not found: {$file}");
            }

            $content = file_get_contents($file);
            if ($content === false) {
                throw new \RuntimeException("Failed to read client config file: {$file}");
            }

            try {
                self::$config = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
            } catch (\JsonException $e) {
                throw new \RuntimeException('Invalid JSON in client config file: ' . $e->getMessage());
            }
        }

        $sub = rtrim($sub, '/');
        $result = [];

        foreach (self::$config['clients'] as $client) {
            $format = (string) ($client['format'] ?? 'clash');
            $subWithFormat = $sub . '/' . $format;

            foreach ($client['platforms'] as $platform => $data) {
                $template = $data['importUrl'] ?? $client['importUrl'] ?? '';
                // {url} = encoded full sub URL with format (same pattern SFA uses successfully).
                // {sub} = raw base sub URL for legacy templates.
                $importUrl = str_replace(
                    ['{url}', '{sub}', '{name}'],
                    [
                        rawurlencode($subWithFormat),
                        $sub,
                        rawurlencode($name),
                    ],
                    $template
                );

                // Path-style hiddify://import/https://... — keep UNENCODED (Hiddify LinkParser
                // takes uri.path as-is and does NOT percent-decode). Prefer query form
                // hiddify://import/?url={url}&name={name} in client_display.json.
                if (str_starts_with($importUrl, 'hiddify://import/') &&
                    ! str_contains($importUrl, 'hiddify://import/?') &&
                    ! str_contains($importUrl, 'hiddify://import?') &&
                    (str_contains($importUrl, '%3A%2F%2F') || str_contains($importUrl, '%3a%2f%2f'))
                ) {
                    // Undo accidental encoding from older templates.
                    $importUrl = self::decodeHiddifyImportPath($importUrl);
                }

                $result[$platform][] = [
                    'name' => $client['name'],
                    'description' => $data['desc'] ?? $client['description'],
                    'format' => $format,
                    'importUrl' => $importUrl,
                    'downloadUrl' => $data['storeUrl'] ??
                        (isset($data['ext']) ? ($r2 ? '/user' : '') . '/clients/' . ($data['file'] ?? str_replace(' ', '.', $client['name'])) . ".{$data['ext']}" : ''),
                    'isAppStore' => isset($data['storeUrl']),
                ];
            }
        }

        return ['clients' => $result, 'icons' => self::$config['icons']];
    }

    /**
     * Decode path-style hiddify://import/https%3A%2F%2F... back to https://...
     * (Hiddify app LinkParser does not decode path; encoded URLs cause connection errors.)
     */
    private static function decodeHiddifyImportPath(string $importUrl): string
    {
        $prefix = 'hiddify://import/';
        $rest = substr($importUrl, strlen($prefix));
        $fragment = '';
        $hashPos = strrpos($rest, '#');

        if ($hashPos !== false) {
            $fragment = substr($rest, $hashPos);
            $rest = substr($rest, 0, $hashPos);
        }

        return $prefix . rawurldecode($rest) . $fragment;
    }
}
