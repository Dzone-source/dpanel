<?php

declare(strict_types=1);

use App\Interfaces\MigrationInterface;
use App\Services\DB;

return new class () implements MigrationInterface {
    public function up(): int
    {
        $pdo = DB::getPdo();
        $pdo->exec('ALTER TABLE `user` ADD COLUMN device_profiles_enabled TINYINT NOT NULL DEFAULT 0, ADD COLUMN device_limit INT UNSIGNED NOT NULL DEFAULT 3');
        $pdo->exec('CREATE TABLE user_device (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            user_id INT UNSIGNED NOT NULL,
            name VARCHAR(100) NOT NULL,
            uuid CHAR(36) NOT NULL UNIQUE,
            password VARCHAR(64) NOT NULL,
            token CHAR(64) NOT NULL UNIQUE,
            created_at INT UNSIGNED NOT NULL,
            revoked_at INT UNSIGNED NULL,
            last_seen_at INT UNSIGNED NULL,
            KEY user_active (user_id, revoked_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
        $pdo->exec('CREATE TABLE user_device_presence (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            device_id INT UNSIGNED NOT NULL,
            node_id INT UNSIGNED NOT NULL,
            ip VARCHAR(45) NOT NULL,
            last_seen_at INT UNSIGNED NOT NULL,
            connections INT UNSIGNED NOT NULL DEFAULT 1,
            UNIQUE KEY device_node_ip (device_id, node_id, ip),
            KEY node_seen (node_id, last_seen_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
        return 2026111700;
    }

    public function down(): int
    {
        DB::getPdo()->exec('DROP TABLE IF EXISTS user_device_presence; DROP TABLE IF EXISTS user_device; ALTER TABLE `user` DROP COLUMN device_profiles_enabled, DROP COLUMN device_limit');
        return 2025073100;
    }
};
