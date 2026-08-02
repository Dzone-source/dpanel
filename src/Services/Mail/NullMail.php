<?php

declare(strict_types=1);

namespace App\Services\Mail;

use RuntimeException;

final class NullMail extends Base
{
    public function __construct()
    {
    }

    public function getConfig(): array
    {
        return [
        ];
    }

    public function send($to, $subject, $body): void
    {
        throw new RuntimeException(
            'Email driver chưa được cấu hình (đang để None). Vui lòng cấu hình SMTP trong Admin → Cài đặt email.'
        );
    }
}
