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

$replacements = [
    "/^\$_ENV\['key'\] = '.*';/m" => "\$_ENV['key'] = '" . addslashes($env['APP_KEY'] ?? 'ChangeMe') . "';",
    "/^\$_ENV\['muKey'\] = '.*';/m" => "\$_ENV['muKey'] = '" . addslashes($env['MU_KEY'] ?? 'ChangeMe') . "';",
    "/^\$_ENV\['appName'\] = '.*';/m" => "\$_ENV['appName'] = '" . addslashes($env['APP_NAME'] ?? 'DPanel') . "';",
    "/^\$_ENV\['baseUrl'\] = '.*';/m" => "\$_ENV['baseUrl'] = '" . addslashes($env['APP_URL'] ?? 'https://example.com') . "';",
    "/^\$_ENV\['db_host'\] = '.*';/m" => "\$_ENV['db_host'] = '" . addslashes($env['DB_HOST'] ?? 'mariadb') . "';",
    "/^\$_ENV\['db_database'\] = '.*';/m" => "\$_ENV['db_database'] = '" . addslashes($env['DB_DATABASE'] ?? 'dpanel') . "';",
    "/^\$_ENV\['db_username'\] = '.*';/m" => "\$_ENV['db_username'] = '" . addslashes($env['DB_USERNAME'] ?? 'dpanel') . "';",
    "/^\$_ENV\['db_password'\] = '.*';/m" => "\$_ENV['db_password'] = '" . addslashes($env['DB_PASSWORD'] ?? '') . "';",
    "/^\$_ENV\['db_port'\] = '.*';/m" => "\$_ENV['db_port'] = '" . addslashes($env['DB_PORT'] ?? '3306') . "';",
    "/^\$_ENV\['redis_host'\] = '.*';/m" => "\$_ENV['redis_host'] = '" . addslashes($env['REDIS_HOST'] ?? 'redis') . "';",
    "/^\$_ENV\['redis_port'\] = .*/m" => "\$_ENV['redis_port'] = " . (int) ($env['REDIS_PORT'] ?? 6379) . ";",
    "/^\$_ENV\['redis_db'\] = .*/m" => "\$_ENV['redis_db'] = " . (int) ($env['REDIS_DB'] ?? 0) . ";",
    "/^\$_ENV\['redis_password'\] = '.*';/m" => "\$_ENV['redis_password'] = '" . addslashes($env['REDIS_PASSWORD'] ?? '') . "';",
    "/^\$_ENV\['timeZone'\] = '.*';/m" => "\$_ENV['timeZone'] = '" . addslashes($env['TZ'] ?? 'Asia/Ho_Chi_Minh') . "';",
    "/^\$_ENV\['locale'\] = '.*';/m" => "\$_ENV['locale'] = '" . addslashes($env['APP_LOCALE'] ?? 'vi_VN') . "';",
];

foreach ($replacements as $pattern => $replacement) {
    $content = preg_replace($pattern, $replacement, $content, 1);
}

file_put_contents($configFile, $content);
echo "Updated config/.config.php from .env\n";
