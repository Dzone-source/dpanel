<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\HourlyUsage;
use App\Models\Invoice;
use App\Models\Node;
use App\Models\User;
use App\Utils\Tools;
use DateTime;
use DateTimeZone;
use Throwable;
use function array_fill;
use function date;
use function floatval;
use function is_null;
use function json_decode;
use function round;
use function strtotime;
use function time;

final class Analytics
{
    /**
     * Paid invoice statuses that count as real revenue.
     * Manual QR is marked paid_admin (no paylist row) — old paylist-only sum missed it.
     *
     * @var list<string>
     */
    private const PAID_INVOICE_STATUSES = ['paid_gateway', 'paid_balance', 'paid_admin'];

    /**
     * Revenue from paid product invoices (package sales).
     * Excludes topup so balance top-up + later balance purchase is not double-counted.
     */
    public static function getIncome(string $req): float
    {
        [$start, $end] = self::incomeRange($req);

        $query = (new Invoice())
            ->whereIn('status', self::PAID_INVOICE_STATUSES)
            ->where('type', 'product')
            ->where('price', '>', 0)
            ->where('pay_time', '>', 0);

        if ($start !== null && $end !== null) {
            $query->whereBetween('pay_time', [$start, $end]);
        }

        $number = $query->sum('price');

        return is_null($number) ? 0.00 : round(floatval($number), 0);
    }

    /**
     * @return array{0: ?int, 1: ?int} unix [start, end] inclusive-ish; nulls = all time
     */
    private static function incomeRange(string $req): array
    {
        if ($req === 'total' || $req === 'default') {
            return [null, null];
        }

        $tzName = (string) ($_ENV['timeZone'] ?? 'Asia/Ho_Chi_Minh');

        try {
            $tz = new DateTimeZone($tzName);
        } catch (Throwable) {
            $tz = new DateTimeZone('Asia/Ho_Chi_Minh');
        }

        $now = new DateTime('now', $tz);
        $todayStart = (clone $now)->setTime(0, 0, 0);

        return match ($req) {
            'today' => [
                $todayStart->getTimestamp(),
                $now->getTimestamp(),
            ],
            'yesterday' => [
                (clone $todayStart)->modify('-1 day')->getTimestamp(),
                $todayStart->getTimestamp() - 1,
            ],
            'this month' => [
                (clone $todayStart)->modify('first day of this month')->getTimestamp(),
                $now->getTimestamp(),
            ],
            default => [null, null],
        };
    }

    public static function getTotalUser(): int
    {
        return (new User())->count();
    }

    public static function getCheckinUser(): int
    {
        return (new User())->where('last_check_in_time', '>', 0)->count();
    }

    public static function getTodayCheckinUser(): int
    {
        return (new User())->where('last_check_in_time', '>', strtotime('today'))->count();
    }

    public static function getTrafficUsage(): string
    {
        return Tools::autoBytes((new User())->sum('u') + (new User())->sum('d'));
    }

    public static function getTodayTrafficUsage(): string
    {
        return Tools::autoBytes((new User())->sum('transfer_today'));
    }

    public static function getRawTodayTrafficUsage(): int
    {
        return (new User())->sum('transfer_today');
    }

    public static function getRawGbTodayTrafficUsage(): float
    {
        return Tools::bToGB((new User())->sum('transfer_today'));
    }

    public static function getLastTrafficUsage(): string
    {
        return Tools::autoBytes((new User())->sum('u') + (new User())->sum('d') - (new User())->sum('transfer_today'));
    }

    public static function getRawLastTrafficUsage(): int
    {
        return (new User())->sum('u') + (new User())->sum('d') - (new User())->sum('transfer_today');
    }

    public static function getRawGbLastTrafficUsage(): float
    {
        return Tools::bToGB((new User())->sum('u') + (new User())->sum('d') - (new User())->sum('transfer_today'));
    }

    public static function getUnusedTrafficUsage(): string
    {
        return Tools::autoBytes((new User())->sum('transfer_enable') - (new User())->sum('u') - (new User())->sum('d'));
    }

    public static function getRawUnusedTrafficUsage(): int
    {
        return (new User())->sum('transfer_enable') - (new User())->sum('u') - (new User())->sum('d');
    }

    public static function getRawGbUnusedTrafficUsage(): float
    {
        return Tools::bToGB((new User())->sum('transfer_enable') - (new User())->sum('u') - (new User())->sum('d'));
    }

    public static function getTotalTraffic(): string
    {
        return Tools::autoBytes((new User())->sum('transfer_enable'));
    }

    public static function getRawTotalTraffic(): int
    {
        return (new User())->sum('transfer_enable');
    }

    public static function getRawGbTotalTraffic(): float
    {
        return Tools::bToGB((new User())->sum('transfer_enable'));
    }

    public static function getTotalNode(): int
    {
        // All enabled nodes — not only ones that have ever heartbeated.
        return (new Node())->where('type', 1)->count();
    }

    public static function getAliveNode(): int
    {
        return (new Node())->where('node_heartbeat', '>', time() - 90)->count();
    }

    public static function getInactiveUser(): int
    {
        return (new User())->where('is_inactive', 1)->count();
    }

    public static function getActiveUser(): int
    {
        return (new User())->where('is_inactive', 0)->count();
    }

    public static function getUserHourlyUsage(int $user_id, string $date): array
    {
        $hourly_usage = (new HourlyUsage())->where('user_id', $user_id)->where('date', $date)->first();

        return $hourly_usage ? json_decode($hourly_usage->usage, true) : array_fill(0, 24, 0);
    }

    public static function getUserTodayHourlyUsage(int $user_id): array
    {
        $date = date('Y-m-d');

        return self::getUserHourlyUsage($user_id, $date);
    }
}
