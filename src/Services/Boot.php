<?php

declare(strict_types=1);

namespace App\Services;

use Exception;
use function date_default_timezone_set;
use function microtime;
use function Sentry\init;
use const PHP_EOL;

final class Boot
{
    public static function setTime(): void
    {
        date_default_timezone_set($_ENV['timeZone']);
        View::$beginTime = microtime(true);

        if (! empty($_ENV['debug'])) {
            error_reporting(E_ALL);
            ini_set('display_errors', '1');
        } else {
            error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING & ~E_DEPRECATED);
            ini_set('display_errors', '0');
        }
    }

    public static function bootDb(): void
    {
        try {
            DB::init();
        } catch (Exception $e) {
            if ($_ENV['debug']) {
                die('Database Error' . PHP_EOL . 'Reason: ' . $e->getMessage());
            }

            die('Database Error');
        }
    }

    public static function bootSentry(): void
    {
        if ($_ENV['sentry_dsn'] !== '') {
            init([
                'dsn' => $_ENV['sentry_dsn'],
            ]);
        }
    }

    public static function normalizeClientIp(): void
    {
        $_SERVER['REMOTE_ADDR'] = \App\Utils\Tools::getClientIp();
    }
}
