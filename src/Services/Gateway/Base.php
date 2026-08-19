<?php

declare(strict_types=1);

namespace App\Services\Gateway;

use App\Models\Config;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\Paylist;
use App\Models\User;
use App\Models\UserCoupon;
use App\Models\UserMoneyLog;
use App\Services\DB;
use App\Services\Reward;
use App\Utils\Tools;
use Psr\Http\Message\ResponseInterface;
use Slim\Http\Response;
use Slim\Http\ServerRequest;
use Throwable;
use voku\helper\AntiXSS;
use function get_called_class;
use function in_array;
use function is_array;
use function is_object;
use function is_string;
use function json_decode;
use function str_contains;
use function time;

abstract class Base
{
    protected AntiXSS $antiXss;

    abstract public function purchase(ServerRequest $request, Response $response, array $args): ResponseInterface;

    abstract public function notify(ServerRequest $request, Response $response, array $args): ResponseInterface;

    /**
     * 支付网关的 codeName
     */
    abstract public static function _name(): string;

    /**
     * 是否启用支付网关
     */
    abstract public static function _enable(): bool;

    /**
     * 显示给用户的名称
     */
    abstract public static function _readableName(): string;

    public function getReturnHTML(ServerRequest $request, Response $response, array $args): ResponseInterface
    {
        return $response->write('ok');
    }

    abstract public static function getPurchaseHTML(): string;

    public function postPayment(string $trade_no): void
    {
        try {
            DB::connection()->transaction(function () use ($trade_no): void {
                $paylist = (new Paylist())->where('tradeno', $trade_no)->lockForUpdate()->first();

                if ($paylist === null || $paylist->status !== 0) {
                    return;
                }

                $paylist->datetime = time();
                $paylist->status = 1;
                $paylist->save();

                $invoice = (new Invoice())->where('id', $paylist->invoice_id)->lockForUpdate()->first();

                if ($invoice === null) {
                    return;
                }

                $was_unpaid = in_array($invoice->status, ['unpaid', 'partially_paid'], true);

                if ($was_unpaid && (int) $paylist->total >= (int) $invoice->price) {
                    $invoice->status = 'paid_gateway';
                    $invoice->update_time = time();
                    $invoice->pay_time = time();
                    $invoice->save();
                }

                $user = (new User())->find($paylist->userid);

                if ($user === null) {
                    return;
                }

                if ($was_unpaid && (int) $paylist->total > (int) $invoice->price) {
                    $overflow = $paylist->total - $invoice->price;
                    $money_before = $user->money;
                    $user->money += $overflow;
                    $user->save();
                    (new UserMoneyLog())->add(
                        $user->id,
                        $money_before,
                        $user->money,
                        $overflow,
                        '超额支付账单 #' . $invoice->id
                    );
                }

                if ($was_unpaid && $invoice->status === 'paid_gateway') {
                    $order = (new Order())->where('id', $invoice->order_id)->lockForUpdate()->first();

                    if ($order !== null) {
                        if ($order->coupon !== '') {
                            $coupon = (new UserCoupon())->where('code', $order->coupon)->lockForUpdate()->first();

                            if ($coupon !== null) {
                                $coupon->use_count += 1;
                                $coupon->save();
                            }
                        }

                        if ($order->status === 'pending_payment') {
                            $order->status = 'pending_activation';
                            $order->update_time = time();
                            $order->save();
                        }
                    }

                    if ($user->ref_by > 0 && Config::obtain('invite_mode') === 'reward') {
                        Reward::issuePaybackReward($user->id, $user->ref_by, $invoice->price, $paylist->invoice_id);
                    }
                }
            });
        } catch (Throwable) {
            // Leave order activation to cron if immediate processing fails.
        }
    }

    public static function generateGuid(): string
    {
        return Tools::genRandomChar();
    }

    protected static function getCallbackUrl(): string
    {
        return $_ENV['baseUrl'] . '/payment/notify/' . get_called_class()::_name();
    }

    protected static function getUserReturnUrl(): string
    {
        return $_ENV['baseUrl'] . '/user/payment/return/' . get_called_class()::_name();
    }

    protected static function getActiveGateway(string $key): bool
    {
        $payment_gateways = (new Config())->where('item', 'payment_gateway')->first();

        if ($payment_gateways === null) {
            return false;
        }

        $raw = (string) $payment_gateways->value;

        if (str_contains($raw, $key)) {
            return true;
        }

        $active_gateways = self::normalizeGatewayList($raw);

        if ($active_gateways === []) {
            $active_gateways = self::normalizeGatewayList(Config::obtain('payment_gateway'));
        }

        return in_array($key, $active_gateways, true);
    }

    protected static function normalizeGatewayList(mixed $value): array
    {
        if (is_string($value)) {
            $decoded = json_decode($value, true);

            if (is_string($decoded)) {
                $decoded = json_decode($decoded, true);
            }

            $value = $decoded;
        }

        if (is_object($value)) {
            $value = (array) $value;
        }

        if (! is_array($value)) {
            return [];
        }

        return array_values(array_map(static fn ($item): string => (string) $item, $value));
    }

    protected static function denyIfNotInvoiceOwner(?Invoice $invoice, User $user, Response $response): ?ResponseInterface
    {
        if ($invoice === null) {
            return $response->withJson([
                'ret' => 0,
                'msg' => 'Invoice not found',
            ]);
        }

        if ((int) $invoice->user_id !== (int) $user->id) {
            return $response->withJson([
                'ret' => 0,
                'msg' => '无权操作此账单',
            ]);
        }

        return null;
    }
}
