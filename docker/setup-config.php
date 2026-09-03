<?php

declare(strict_types=1);

/**
 * Generate config/.config.php from .env for Docker deployments.
 */

$root = dirname(__DIR__);
$envFile = $root . '/.env';

if (! is_readable($envFile)) {
    fwrite(STDERR, ".env file not found. Run install.sh first.\n");
    exit(1);
}

$env = [];
foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
    $line = trim($line);
    if ($line === '' || str_starts_with($line, '#')) {
        continue;
    }
    [$key, $value] = array_pad(explode('=', $line, 2), 2, '');
    $env[trim($key)] = trim($value);
}

$configFile = $root . '/config/.config.php';
if (! is_readable($configFile)) {
    fwrite(STDERR, "config/.config.php not found.\n");
    exit(1);
}

$content = file_get_contents($configFile);

/**
 * Replace a $_ENV['key'] = '...' assignment (optional trailing comment preserved).
 */
$replaceString = static function (string $content, string $key, string $value): string {
    $pattern = '/^\$_ENV\[\'' . preg_quote($key, '/') . '\'\]\s*=\s*\'[^\']*\';(.*)$/m';
    $replacement = '\$_ENV[\'' . $key . '\'] = \'' . addcslashes($value, '\\\'') . '\';$1';

    $updated = preg_replace($pattern, $replacement, $content, 1, $count);
    if ($count !== 1) {
        fwrite(STDERR, "Warning: could not update \$_ENV['{$key}']\n");
    }

    return $updated ?? $content;
};

$replaceInt = static function (string $content, string $key, int $value): string {
    $pattern = '/^\$_ENV\[\'' . preg_quote($key, '/') . '\'\]\s*=\s*[^;]+;(.*)$/m';
    $replacement = '\$_ENV[\'' . $key . '\'] = ' . $value . ';$1';
    $updated = preg_replace($pattern, $replacement, $content, 1, $count);
    if ($count !== 1) {
        fwrite(STDERR, "Warning: could not update \$_ENV['{$key}']\n");
    }

    return $updated ?? $content;
};

$content = $replaceString($content, 'key', $env['APP_KEY'] ?? 'ChangeMe');
$content = $replaceString($content, 'muKey', $env['MU_KEY'] ?? 'ChangeMe');
$content = $replaceString($content, 'appName', $env['APP_NAME'] ?? 'DPanel');
$content = $replaceString($content, 'baseUrl', $env['APP_URL'] ?? 'https://example.com');
$content = $replaceString($content, 'db_host', $env['DB_HOST'] ?? 'mariadb');
$content = $replaceString($content, 'db_database', $env['DB_DATABASE'] ?? 'dpanel');
$content = $replaceString($content, 'db_username', $env['DB_USERNAME'] ?? 'dpanel');
$content = $replaceString($content, 'db_password', $env['DB_PASSWORD'] ?? '');
$content = $replaceString($content, 'db_port', $env['DB_PORT'] ?? '3306');
$content = $replaceString($content, 'redis_host', $env['REDIS_HOST'] ?? 'redis');
$content = $replaceInt($content, 'redis_port', (int) ($env['REDIS_PORT'] ?? 6379));
$content = $replaceInt($content, 'redis_db', (int) ($env['REDIS_DB'] ?? 0));
$content = $replaceString($content, 'redis_password', $env['REDIS_PASSWORD'] ?? '');
$content = $replaceString($content, 'timeZone', $env['TZ'] ?? 'Asia/Ho_Chi_Minh');
$content = $replaceString($content, 'locale', $env['APP_LOCALE'] ?? 'vi_VN');

file_put_contents($configFile, $content);
echo "Updated config/.config.php from .env\n";
