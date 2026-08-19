<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Query\Builder;
use function strtotime;
use function time;

/**
 * @property int    $id     记录ID
 * @property string $type   订阅token
 * @property int    $userid 用户ID
 *
 * @mixin Builder
 */
final class Link extends Model
{
    protected $connection = 'default';
    protected $table = 'link';

    public function user(): ?User
    {
        return (new User())->find($this->attributes['userid']);
    }

    public function isValid(): bool
    {
        $user = $this->user();

        if ($user === null || $user->is_banned !== 0) {
            return false;
        }

        if (strtotime($user->class_expire) <= time()) {
            return false;
        }

        return true;
    }
}
