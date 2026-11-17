<?php

declare(strict_types=1);

// Isolated MariaDB fixture only. Never reads the application's production config.
if (getenv('DEVICE_TEST_MYSQL') !== '1') {
    exit(99);
}
require dirname(__DIR__, 2) . '/vendor/autoload.php';
$db = new App\Services\DB();
$db->addConnection(['driver' => 'mysql', 'host' => 'dpanel-device-db', 'database' => 'device_profiles_test', 'username' => 'root', 'password' => 'device-test-only', 'charset' => 'utf8mb4', 'collation' => 'utf8mb4_unicode_ci']);
$db->setAsGlobal();
$db->bootEloquent();
try {
    App\Services\DeviceProfiles::create((int) $argv[1], 'Concurrent fixture');
    exit(0);
} catch (DomainException) {
    exit(10);
}
