<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Query\Builder;

/**
 * @property int    $id       工单ID
 * @property string $title    工单标题
 * @property string $content  工单内容
 * @property int    $userid   用户ID
 * @property int    $datetime 创建时间
 * @property string $status   工单状态
 * @property string $type     工单类型
 *
 * @mixin Builder
 */
final class Ticket extends Model
{
    protected $connection = 'default';
    protected $table = 'ticket';

    /**
     * 工单类型
     */
    public function type(): string
    {
        return match ($this->type) {
            'howto' => 'Hướng dẫn',
            'billing' => 'Tài chính',
            'account' => 'Tài khoản',
            default => 'Khác',
        };
    }

    /**
     * 工单状态
     */
    public function status(): string
    {
        return match ($this->status) {
            'closed' => 'Đã đóng',
            'open_wait_user' => 'Chờ phản hồi người dùng',
            'open_wait_admin' => 'Đang xử lý',
            default => 'Không xác định',
        };
    }
}
