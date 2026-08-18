<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Ann;
use App\Models\Config;
use App\Models\DetectLog;
use App\Models\EmailQueue;
use App\Models\HourlyUsage;
use App\Models\Invoice;
use App\Models\Node;
use App\Models\OnlineLog;
use App\Models\Order;
use App\Models\SubscribeLog;
use App\Models\User;
use App\Models\UserMoneyLog;
use App\Utils\Tools;
use DateTime;
use DateTimeZone;
use Exception;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Http\Client\ClientExceptionInterface;
use Telegram\Bot\Exceptions\TelegramSDKException;
use function array_map;
use function date;
use function in_array;
use function json_decode;
use function ob_end_clean;
use function ob_start;
use function str_replace;
use function strtotime;
use function time;
use const PHP_EOL;

final class Cron
{
    public static function cleanDb(): void
    {
        (new SubscribeLog())->where(
            'request_time',
            '<',
            time() - 86400 * Config::obtain('subscribe_log_retention_days')
        )->delete();
        (new HourlyUsage())->where(
            'date',
            '<',
            date('Y-m-d', time() - 86400 * Config::obtain('traffic_log_retention_days'))
        )->delete();
        (new DetectLog())->where('datetime', '<', time() - 86400 * 3)->delete();
        (new EmailQueue())->where('time', '<', time() - 86400)->delete();
        (new OnlineLog())->where('last_time', '<', time() - 86400)->delete();

        echo Tools::toDateTime(time()) . ' 数据库清理完成' . PHP_EOL;
    }

    public static function detectInactiveUser(): void
    {
        $checkin_days = Config::obtain('detect_inactive_user_checkin_days');
        $login_days = Config::obtain('detect_inactive_user_login_days');
        $use_days = Config::obtain('detect_inactive_user_use_days');

        (new User())->where('is_admin', 0)
            ->where('is_inactive', 0)
            ->where('last_check_in_time', '<', time() - 86400 * $checkin_days)
            ->where('last_login_time', '<', time() - 86400 * $login_days)
            ->where('last_use_time', '<', time() - 86400 * $use_days)
            ->update(['is_inactive' => 1]);

        (new User())->where('is_admin', 0)
            ->where('is_inactive', 1)
            ->where('last_check_in_time', '>', time() - 86400 * $checkin_days)
            ->where('last_login_time', '>', time() - 86400 * $login_days)
            ->where('last_use_time', '>', time() - 86400 * $use_days)
            ->update(['is_inactive' => 0]);

        echo Tools::toDateTime(time()) .
            ' 检测到 ' . (new User())->where('is_inactive', 1)->count() . ' 个账户处于闲置状态' . PHP_EOL;
    }

    public static function detectNodeOffline(): void
    {
        $nodes = (new Node())->where('type', 1)->get();

        foreach ($nodes as $node) {
            if ($node->getNodeOnlineStatus() >= 0 && $node->online === 1) {
                continue;
            }

            if ($node->getNodeOnlineStatus() === -1 && $node->online === 1) {
                echo 'Send Node Offline Email to admin users' . PHP_EOL;

                try {
                    Notification::notifyAdmin(
                        $_ENV['appName'] . '-Cảnh báo hệ thống',
                        'Xin chào quản trị viên, hệ thống phát hiện nút ' . $node->name . ' đã ngắt kết nối, vui lòng xử lý kịp thời.'
                    );
                } catch (GuzzleException|ClientExceptionInterface|TelegramSDKException $e) {
                    echo $e->getMessage() . PHP_EOL;
                }

                if (Config::obtain('im_bot_group_notify_node_offline')) {
                    try {
                        Notification::notifyUserGroup(
                            str_replace(
                                '%node_name%',
                                $node->name,
                                I18n::trans('bot.node_offline', $_ENV['locale'])
                            ),
                        );
                    } catch (TelegramSDKException | GuzzleException $e) {
                        echo $e->getMessage() . PHP_EOL;
                    }
                }

                $node->online = 0;
                $node->save();

                continue;
            }

            if ($node->getNodeOnlineStatus() === 1 && $node->online === 0) {
                echo 'Send Node Online Email to admin user' . PHP_EOL;

                try {
                    Notification::notifyAdmin(
                        $_ENV['appName'] . '-Thông báo hệ thống',
                        'Xin chào quản trị viên, hệ thống phát hiện nút ' . $node->name . ' đã trực tuyến trở lại.'
                    );
                } catch (GuzzleException|ClientExceptionInterface|TelegramSDKException $e) {
                    echo $e->getMessage() . PHP_EOL;
                }

                if (Config::obtain('im_bot_group_notify_node_online')) {
                    try {
                        Notification::notifyUserGroup(
                            str_replace(
                                '%node_name%',
                                $node->name,
                                I18n::trans('bot.node_online', $_ENV['locale'])
                            ),
                        );
                    } catch (TelegramSDKException | GuzzleException $e) {
                        echo $e->getMessage() . PHP_EOL;
                    }
                }

                $node->online = 1;
                $node->save();
            }
        }

        echo Tools::toDateTime(time()) . ' 节点离线检测完成' . PHP_EOL;
    }

    public static function expirePaidUserAccount(): void
    {
        $paidUsers = (new User())->where('class', '>', 0)->get();

        foreach ($paidUsers as $user) {
            if (strtotime($user->class_expire) < time()) {
                $text = 'Xin chào, hệ thống phát hiện cấp tài khoản của bạn đã hết hạn.';
                $reset_traffic = $_ENV['class_expire_reset_traffic'];

                if ($reset_traffic >= 0) {
                    $user->transfer_enable = Tools::gbToB($reset_traffic);
                    $text .= 'Lưu lượng đã được đặt lại thành ' . $reset_traffic . 'GB.';
                }

                try {
                    Notification::notifyUser($user, $_ENV['appName'] . '-Cấp tài khoản của bạn đã hết hạn', $text);
                } catch (GuzzleException|ClientExceptionInterface|TelegramSDKException $e) {
                    echo $e->getMessage() . PHP_EOL;
                }

                $user->u = 0;
                $user->d = 0;
                $user->transfer_today = 0;
                $user->class = 0;
                $user->save();
            }
        }

        echo Tools::toDateTime(time()) . ' 付费用户过期检测完成' . PHP_EOL;
    }

    public static function processEmailQueue(): void
    {
        if ((new EmailQueue())->count() === 0) {
            echo Tools::toDateTime(time()) . ' 邮件队列为空' . PHP_EOL;
        } else {
            //记录当前时间戳
            $timestamp = time();
            //邮件队列处理
            while (true) {
                if (time() - $timestamp > 299) {
                    echo Tools::toDateTime(time()) . '邮件队列处理超时，已跳过' . PHP_EOL;
                    break;
                }

                DB::beginTransaction();
                $email_queues_raw = DB::select('SELECT * FROM email_queue LIMIT 1 FOR UPDATE SKIP LOCKED');

                if (count($email_queues_raw) === 0) {
                    DB::commit();
                    break;
                }

                $email_queues = array_map(static function ($value) {
                    return (array) $value;
                }, $email_queues_raw);
                $email_queue = $email_queues[0];
                echo '发送邮件至 ' . $email_queue['to_email'] . PHP_EOL;
                DB::delete('DELETE FROM email_queue WHERE id = ?', [$email_queue['id']]);

                if (Tools::isEmail($email_queue['to_email'])) {
                    try {
                        Mail::send(
                            $email_queue['to_email'],
                            $email_queue['subject'],
                            $email_queue['template'],
                            json_decode($email_queue['array'])
                        );
                    } catch (Exception|ClientExceptionInterface $e) {
                        echo $e->getMessage();
                    }
                } else {
                    echo $email_queue['to_email'] . ' 邮箱格式错误，已跳过' . PHP_EOL;
                }

                DB::commit();
            }

            echo Tools::toDateTime(time()) . ' 邮件队列处理完成' . PHP_EOL;
        }
    }

    public static function processTabpOrderActivation(): void
    {
        $users = User::all();

        foreach ($users as $user) {
            $user_id = $user->id;
            // Một user chỉ có 1 đơn TABP đang activated tại một thời điểm.
            $activated_order = (new Order())->where('user_id', $user_id)
                ->where('status', 'activated')
                ->where('product_type', 'tabp')
                ->orderBy('id')
                ->first();
            $pending_activation_orders = (new Order())->where('user_id', $user_id)
                ->where('status', 'pending_activation')
                ->where('product_type', 'tabp')
                ->orderBy('id')
                ->get();

            if ($activated_order !== null) {
                $content = json_decode($activated_order->product_content);
                $duration_days = (int) ($content->option_days
                    ?? $content->time
                    ?? $content->class_time
                    ?? 0);

                if ($duration_days > 0 && $activated_order->update_time + $duration_days * 86400 < time()) {
                    $activated_order->status = 'expired';
                    $activated_order->update_time = time();
                    $activated_order->save();
                    echo "TABP订单 #{$activated_order->id} 已过期。\n";
                    $activated_order = null;
                }
            }

            // Đổi gói / mua thêm cùng sản phẩm (gia hạn 1 năm, 10 năm...):
            // kích hoạt đơn mới ngay, không xếp hàng sau gói ngắn đang chạy.
            $order_to_activate = null;
            $stack_expire = false;

            if ($activated_order !== null && count($pending_activation_orders) > 0) {
                $activated_content = json_decode($activated_order->product_content);
                $activated_group = (int) ($activated_content->node_group ?? 0);
                $pending_order = $pending_activation_orders->sortByDesc('id')->first();
                $pending_content = json_decode($pending_order->product_content);
                $pending_group = (int) ($pending_content->node_group ?? 0);
                $same_product = (int) $pending_order->product_id === (int) $activated_order->product_id
                    && $pending_group === $activated_group;

                // Cùng sản phẩm (gia hạn) hoặc đổi gói: hết hạn đơn cũ và kích hoạt đơn mới.
                $activated_order->status = 'expired';
                $activated_order->update_time = time();
                $activated_order->save();
                echo $same_product
                    ? "TABP订单 #{$activated_order->id} 已因续费/升级而过期。\n"
                    : "TABP订单 #{$activated_order->id} 已因切换套餐而过期。\n";
                $activated_order = null;
                $order_to_activate = $pending_order;
                $stack_expire = $same_product;
            }

            if ($order_to_activate === null && $activated_order === null && count($pending_activation_orders) > 0) {
                $order_to_activate = $pending_activation_orders[0];
            }

            if ($order_to_activate !== null) {
                $content = json_decode($order_to_activate->product_content);
                $duration_days = (int) ($content->option_days
                    ?? $content->class_time
                    ?? $content->time
                    ?? 0);
                if ($duration_days <= 0) {
                    $duration_days = 30;
                }

                $user->u = 0;
                $user->d = 0;
                $user->transfer_today = 0;
                $user->transfer_enable = Tools::gbToB($content->bandwidth);
                $user->class = $content->class;

                $base_ts = time();
                if ($stack_expire) {
                    $current_expire_ts = strtotime((string) $user->class_expire);
                    if ($current_expire_ts !== false && $current_expire_ts > $base_ts) {
                        $base_ts = $current_expire_ts;
                    }
                }
                $old_class_expire = (new DateTime())->setTimestamp($base_ts);
                $user->class_expire = $old_class_expire
                    ->modify('+' . $duration_days . ' days')->format('Y-m-d H:i:s');
                $user->node_group = $content->node_group;
                $user->node_speedlimit = $content->speed_limit;
                $user->node_iplimit = $content->ip_limit;
                $user->save();
                $order_to_activate->status = 'activated';
                $order_to_activate->update_time = time();
                $order_to_activate->save();
                echo "TABP订单 #{$order_to_activate->id} 已激活（{$duration_days} ngày）。\n";
            }
        }

        echo Tools::toDateTime(time()) . ' TABP订单激活处理完成' . PHP_EOL;
    }

    public static function processBandwidthOrderActivation(): void
    {
        $users = User::all();

        foreach ($users as $user) {
            $user_id = $user->id;
            // 获取用户账户等待激活的流量包订单
            $order = (new Order())->where('user_id', $user_id)
                ->where('status', 'pending_activation')
                ->where('product_type', 'bandwidth')
                ->orderBy('id')
                ->first();

            if ($order !== null) {
                // 获取流量包订单内容准备激活
                $content = json_decode($order->product_content);
                // 激活流量包
                $user->transfer_enable += Tools::gbToB($content->bandwidth);
                $user->save();
                $order->status = 'activated';
                $order->update_time = time();
                $order->save();
                echo "流量包订单 #{$order->id} 已激活。\n";
            }
        }

        echo Tools::toDateTime(time()) . ' 流量包订单激活处理完成' . PHP_EOL;
    }

    /**
     * @throws Exception
     */
    public static function processTimeOrderActivation(): void
    {
        $users = User::all();

        foreach ($users as $user) {
            $user_id = $user->id;
            // 获取用户账户等待激活的时间包订单
            $order = (new Order())->where('user_id', $user_id)
                ->where('status', 'pending_activation')
                ->where('product_type', 'time')
                ->orderBy('id')
                ->first();

            if ($order !== null) {
                $content = json_decode($order->product_content);
                // 跳过当前账户等级不等于时间包等级的非免费用户订单
                if ($user->class !== (int) $content->class && $user->class > 0) {
                    continue;
                }
                $duration_days = (int) ($content->option_days
                    ?? $content->class_time
                    ?? $content->time
                    ?? 0);
                if ($duration_days <= 0) {
                    continue;
                }
                // 激活时间包
                $user->class = $content->class;
                $expire_ts = strtotime((string) $user->class_expire);
                $base_ts = ($expire_ts !== false && $expire_ts > time()) ? $expire_ts : time();
                $old_class_expire = (new DateTime())->setTimestamp($base_ts);
                $user->class_expire = $old_class_expire
                    ->modify('+' . $duration_days . ' days')->format('Y-m-d H:i:s');
                $user->node_group = $content->node_group;
                $user->node_speedlimit = $content->speed_limit;
                $user->node_iplimit = $content->ip_limit;
                $user->save();
                $order->status = 'activated';
                $order->update_time = time();
                $order->save();
                echo "时间包订单 #{$order->id} 已激活（{$duration_days} ngày）。\n";
            }
        }

        echo Tools::toDateTime(time()) . ' 时间包订单激活处理完成' . PHP_EOL;
    }

    /**
     * @throws Exception
     */
    public static function processTopupOrderActivation(): void
    {
        // 获取等待激活的充值订单，允许同时处理多个充值订单
        $orders = (new Order())->where('status', 'pending_activation')
            ->where('product_type', 'topup')
            ->orderBy('id')
            ->get();

        foreach ($orders as $order) {
            $user_id = $order->user_id;
            $user = (new User())->find($user_id);
            $content = json_decode($order->product_content);
            // 充值
            $user->money += $content->amount;
            $user->save();
            $order->status = 'activated';
            $order->update_time = time();
            $order->save();
            (new UserMoneyLog())->add(
                $user_id,
                $user->money - $content->amount,
                $user->money,
                $content->amount,
                "Đơn nạp tiền #{$order->id}"
            );
            echo "充值订单 #{$order->id} 已激活。\n";
        }

        echo Tools::toDateTime(time()) . ' 充值订单激活处理完成' . PHP_EOL;
    }

    public static function processPendingOrder(): void
    {
        $pending_payment_orders = (new Order())->where('status', 'pending_payment')->get();

        foreach ($pending_payment_orders as $order) {
            // 检查账单支付状态
            $invoice = (new Invoice())->where('order_id', $order->id)->first();

            if ($invoice === null) {
                continue;
            }
            // 标记订单为等待激活
            if (in_array($invoice->status, ['paid_gateway', 'paid_balance', 'paid_admin'])) {
                $order->status = 'pending_activation';
                $order->update_time = time();
                $order->save();
                echo "已标记订单 #{$order->id} 为等待激活。\n";
                continue;
            }
            // 取消超时未支付的订单和关联账单，跳过账单已经部分支付的订单
            if ($order->create_time + 86400 < time() && $invoice->status !== 'partially_paid') {
                $order->status = 'cancelled';
                $order->update_time = time();
                $order->save();
                echo "已取消超时订单 #{$order->id}。\n";
                $invoice->status = 'cancelled';
                $invoice->update_time = time();
                $invoice->save();
                echo "已取消超时账单 #{$invoice->id}。\n";
            }
        }

        echo Tools::toDateTime(time()) . ' 等待中订单处理完成' . PHP_EOL;
    }

    /**
     * Immediately flip paid invoices to activation and activate shop orders.
     * Used by admin mark-paid / payment callbacks so users do not wait for cron.
     *
     * @throws Exception
     */
    public static function processShopOrdersNow(): void
    {
        // The activation routines echo progress for the CLI cron. Called from a
        // web request that output lands in the response body ahead of the JSON,
        // which the browser then cannot parse, so capture and drop it here.
        ob_start();

        try {
            self::processPendingOrder();
            self::processTabpOrderActivation();
            self::processBandwidthOrderActivation();
            self::processTimeOrderActivation();
            self::processTopupOrderActivation();
        } finally {
            ob_end_clean();
        }
    }

    public static function removeInactiveUserLinkAndInvite(): void
    {
        $inactive_users = (new User())->where('is_inactive', 1)->get();

        foreach ($inactive_users as $user) {
            $user->removeLink();
            $user->removeInvite();
        }

        echo Tools::toDateTime(time()) . ' Successfully removed inactive user\'s Link and Invite' . PHP_EOL;
    }

    public static function resetNodeBandwidth(): void
    {
        (new Node())->where('bandwidthlimit_resetday', date('d'))->update(['node_bandwidth' => 0]);

        echo Tools::toDateTime(time()) . ' 重设节点流量完成' . PHP_EOL;
    }

    public static function resetTodayBandwidth(): void
    {
        (new User())->query()->update(['transfer_today' => 0]);

        echo Tools::toDateTime(time()) . ' 重设用户每日流量完成' . PHP_EOL;
    }

    public static function resetFreeUserBandwidth(): void
    {
        $freeUsers = (new User())->where('class', 0)
            ->where('auto_reset_day', date('d'))->get();

        foreach ($freeUsers as $user) {
            try {
                Notification::notifyUser(
                    $user,
                    $_ENV['appName'] . '-Thông báo đặt lại lưu lượng miễn phí',
                    'Xin chào, lưu lượng miễn phí của bạn đã được đặt lại thành ' . $user->auto_reset_bandwidth . 'GB.'
                );
            } catch (GuzzleException|ClientExceptionInterface|TelegramSDKException $e) {
                echo $e->getMessage() . PHP_EOL;
            }

            $user->u = 0;
            $user->d = 0;
            $user->transfer_enable = $user->auto_reset_bandwidth * 1024 * 1024 * 1024;
            $user->save();
        }

        echo Tools::toDateTime(time()) . ' 免费用户流量重置完成' . PHP_EOL;
    }

    public static function sendDailyFinanceMail(): void
    {
        $yesterday = Analytics::getIncome('yesterday');
        [$start, $end] = self::financeDayBounds(-1);

        $invoices = (new Invoice())
            ->whereIn('status', ['paid_gateway', 'paid_balance', 'paid_admin'])
            ->where('type', 'product')
            ->where('price', '>', 0)
            ->where('pay_time', '>', 0)
            ->whereBetween('pay_time', [$start, $end])
            ->orderBy('pay_time')
            ->get();

        if (count($invoices) === 0) {
            echo 'No paid product invoices found for yesterday' . PHP_EOL;

            return;
        }

        $text_html = '<table><tr><td>Số tiền</td><td>Hóa đơn</td><td>User ID</td><td>Trạng thái</td><td>Thời gian thanh toán</td></tr>';

        foreach ($invoices as $invoice) {
            $text_html .= '<tr>';
            $text_html .= '<td>' . Tools::formatVnd((float) $invoice->price, 0, true) . '</td>';
            $text_html .= '<td>#' . $invoice->id . '</td>';
            $text_html .= '<td>' . $invoice->user_id . '</td>';
            $text_html .= '<td>' . $invoice->status . '</td>';
            $text_html .= '<td>' . Tools::toDateTime((int) $invoice->pay_time) . '</td>';
            $text_html .= '</tr>';
        }

        $text_html .= '</table>';
        $text_html .= '<br>Tổng số hóa đơn hôm qua: ' . count($invoices)
            . '<br>Tổng doanh thu hôm qua: ' . Tools::formatVnd($yesterday, 0, true);

        $text_html = str_replace([
            '<table>',
            '<tr>',
            '<td>',
        ], [
            '<table style="width: 100%;border: 1px solid black;border-collapse: collapse;">',
            '<tr style="border: 1px solid black;padding: 5px;">',
            '<td style="border: 1px solid black;padding: 5px;">',
        ], $text_html);

        echo 'Sending daily finance email to admin user' . PHP_EOL;

        try {
            Notification::notifyAdmin(
                'Báo cáo tài chính hàng ngày',
                $text_html,
                'finance.tpl'
            );
        } catch (GuzzleException|ClientExceptionInterface|TelegramSDKException $e) {
            echo $e->getMessage() . PHP_EOL;
        }

        echo Tools::toDateTime(time()) . ' Successfully sent daily finance email' . PHP_EOL;
    }

    public static function sendWeeklyFinanceMail(): void
    {
        [$start, $end] = self::financeDayBounds(-7, -1);
        $total = self::sumProductRevenueBetween($start, $end);
        $count = (new Invoice())
            ->whereIn('status', ['paid_gateway', 'paid_balance', 'paid_admin'])
            ->where('type', 'product')
            ->where('price', '>', 0)
            ->where('pay_time', '>', 0)
            ->whereBetween('pay_time', [$start, $end])
            ->count();

        $text_html = '<br>Tổng số hóa đơn 7 ngày qua: ' . $count
            . '<br>Tổng doanh thu 7 ngày qua: ' . Tools::formatVnd($total, 0, true);
        echo 'Sending weekly finance email to admin user' . PHP_EOL;

        try {
            Notification::notifyAdmin(
                'Báo cáo tài chính hàng tuần',
                $text_html,
                'finance.tpl'
            );
        } catch (GuzzleException|ClientExceptionInterface|TelegramSDKException $e) {
            echo $e->getMessage() . PHP_EOL;
        }

        echo Tools::toDateTime(time()) . ' Successfully sent weekly finance email' . PHP_EOL;
    }

    public static function sendMonthlyFinanceMail(): void
    {
        [$start, $end] = self::financeMonthBounds();
        $total = self::sumProductRevenueBetween($start, $end);
        $count = (new Invoice())
            ->whereIn('status', ['paid_gateway', 'paid_balance', 'paid_admin'])
            ->where('type', 'product')
            ->where('price', '>', 0)
            ->where('pay_time', '>', 0)
            ->whereBetween('pay_time', [$start, $end])
            ->count();

        $text_html = '<br>Tổng số hóa đơn tháng trước: ' . $count
            . '<br>Tổng doanh thu tháng trước: ' . Tools::formatVnd($total, 0, true);
        echo 'Sending monthly finance email to admin user' . PHP_EOL;

        try {
            Notification::notifyAdmin(
                'Báo cáo tài chính hàng tháng',
                $text_html,
                'finance.tpl'
            );
        } catch (GuzzleException|ClientExceptionInterface|TelegramSDKException $e) {
            echo $e->getMessage() . PHP_EOL;
        }

        echo Tools::toDateTime(time()) . ' Successfully sent monthly finance email' . PHP_EOL;
    }

    /**
     * @return array{0: int, 1: int}
     */
    private static function financeDayBounds(int $fromDaysAgo, ?int $toDaysAgo = null): array
    {
        $toDaysAgo ??= $fromDaysAgo;
        $tzName = (string) ($_ENV['timeZone'] ?? 'Asia/Ho_Chi_Minh');

        try {
            $tz = new DateTimeZone($tzName);
        } catch (Exception) {
            $tz = new DateTimeZone('Asia/Ho_Chi_Minh');
        }

        $todayStart = (new DateTime('now', $tz))->setTime(0, 0, 0);
        $start = (clone $todayStart)->modify($fromDaysAgo . ' day')->getTimestamp();
        $end = (clone $todayStart)->modify($toDaysAgo . ' day')->modify('+1 day')->getTimestamp() - 1;

        return [$start, $end];
    }

    /**
     * @return array{0: int, 1: int}
     */
    private static function financeMonthBounds(): array
    {
        $tzName = (string) ($_ENV['timeZone'] ?? 'Asia/Ho_Chi_Minh');

        try {
            $tz = new DateTimeZone($tzName);
        } catch (Exception) {
            $tz = new DateTimeZone('Asia/Ho_Chi_Minh');
        }

        $now = new DateTime('now', $tz);
        $start = (clone $now)->modify('first day of last month')->setTime(0, 0, 0)->getTimestamp();
        $end = (clone $now)->modify('first day of this month')->setTime(0, 0, 0)->getTimestamp() - 1;

        return [$start, $end];
    }

    private static function sumProductRevenueBetween(int $start, int $end): float
    {
        $number = (new Invoice())
            ->whereIn('status', ['paid_gateway', 'paid_balance', 'paid_admin'])
            ->where('type', 'product')
            ->where('price', '>', 0)
            ->where('pay_time', '>', 0)
            ->whereBetween('pay_time', [$start, $end])
            ->sum('price');

        return round((float) ($number ?? 0), 0);
    }

    public static function sendPaidUserUsageLimitNotification(): void
    {
        $paidUsers = (new User())->where('class', '>', 0)->get();

        foreach ($paidUsers as $user) {
            $user_traffic_left = $user->transfer_enable - $user->u - $user->d;
            $under_limit = false;
            $unit_text = '';

            if ($_ENV['notify_limit_mode'] === 'per' &&
                $user_traffic_left / $user->transfer_enable * 100 < $_ENV['notify_limit_value']
            ) {
                $under_limit = true;
                $unit_text = '%';
            } elseif ($_ENV['notify_limit_mode'] === 'mb' &&
                Tools::bToMB($user_traffic_left) < $_ENV['notify_limit_value']
            ) {
                $under_limit = true;
                $unit_text = 'MB';
            }

            if ($under_limit && ! $user->traffic_notified) {
                try {
                    Notification::notifyUser(
                        $user,
                        $_ENV['appName'] . '-Lưu lượng còn lại quá thấp',
                        'Xin chào, hệ thống phát hiện lưu lượng còn lại của bạn đã dưới ' . $_ENV['notify_limit_value'] . $unit_text . '.',
                    );

                    $user->traffic_notified = true;
                } catch (GuzzleException|ClientExceptionInterface|TelegramSDKException $e) {
                    $user->traffic_notified = false;
                    echo $e->getMessage() . PHP_EOL;
                }

                $user->save();
            } elseif (! $under_limit && $user->traffic_notified) {
                $user->traffic_notified = false;
                $user->save();
            }
        }

        echo Tools::toDateTime(time()) . ' 付费用户用量限制提醒完成' . PHP_EOL;
    }

    public static function sendDailyTrafficReport(): void
    {
        $users = (new User())->whereIn('daily_mail_enable', [1, 2])->get();
        $ann_latest_raw = (new Ann())->where('status', '>', 0)
            ->orderBy('status', 'desc')
            ->orderBy('sort')
            ->orderBy('date', 'desc')->first();

        if ($ann_latest_raw === null) {
            $ann_latest = '<br><br>';
        } else {
            $ann_latest = $ann_latest_raw->content . '<br><br>';
        }

        foreach ($users as $user) {
            $user->sendDailyNotification($ann_latest);
        }

        echo Tools::toDateTime(time()) . ' Successfully sent daily traffic report' . PHP_EOL;
    }

    public static function sendDailyJobNotification(): void
    {
        try {
            Notification::notifyUserGroup(
                I18n::trans('bot.daily_job_run', $_ENV['locale'])
            );
        } catch (TelegramSDKException | GuzzleException $e) {
            echo $e->getMessage() . PHP_EOL;
        }

        echo Tools::toDateTime(time()) . ' Successfully sent daily job notification' . PHP_EOL;
    }

    public static function sendDiaryNotification(): void
    {
        try {
            Notification::notifyUserGroup(
                str_replace(
                    [
                        '%checkin_user%',
                        '%lastday_total%',
                    ],
                    [
                        Analytics::getTodayCheckinUser(),
                        Analytics::getTodayTrafficUsage(),
                    ],
                    I18n::trans('bot.diary', $_ENV['locale'])
                )
            );
        } catch (TelegramSDKException | GuzzleException $e) {
            echo $e->getMessage() . PHP_EOL;
        }

        echo Tools::toDateTime(time()) . ' Successfully sent diary notification' . PHP_EOL;
    }

    public static function updateNodeIp(): void
    {
        $nodes = (new Node())->where('type', 1)->get();

        foreach ($nodes as $node) {
            $node->updateNodeIp();
            $node->save();
        }

        echo Tools::toDateTime(time()) . ' 更新节点 IP 完成' . PHP_EOL;
    }
}
