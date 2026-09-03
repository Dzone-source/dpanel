<?php

declare(strict_types=1);

use App\Interfaces\MigrationInterface;
use App\Services\DB;

return new class() implements MigrationInterface {
    public function up(): int
    {
        DB::getPdo()->exec("
            UPDATE user SET locale = 'vi_VN' WHERE locale <> 'vi_VN';
            ALTER TABLE user MODIFY COLUMN locale varchar(16) NOT NULL DEFAULT 'vi_VN' COMMENT 'Ngôn ngữ hiển thị';
        ");

        return 2025071400;
    }

    public function down(): int
    {
        DB::getPdo()->exec("
            ALTER TABLE user MODIFY COLUMN locale varchar(16) NOT NULL DEFAULT 'zh-TW' COMMENT '显示语言';
        ");

        return 2025073100;
    }
};
