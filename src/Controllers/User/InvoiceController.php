<?php

declare(strict_types=1);

namespace App\Controllers\User;

use App\Controllers\BaseController;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\Paylist;
use App\Models\User;
use App\Models\UserMoneyLog;
use App\Services\Cron as CronService;
use App\Services\DB;
use App\Services\Gateway\ManualQr;
use App\Services\Payment;
use App\Utils\Tools;
use Exception;
use Psr\Http\Message\ResponseInterface;
use Slim\Http\Response;
use Slim\Http\ServerRequest;
use function array_values;
use function count;
use function in_array;
use function is_array;
use function json_decode;
use function json_encode;
use function ltrim;
use function round;
use function time;

final class InvoiceController extends BaseController
{
    private static array $details = [
        'field' => [
            'op' => 'Thao tác',
            'id' => 'ID hóa đơn',
            'order_id' => 'ID đơn hàng',
            'price' => 'Số tiền hóa đơn',
            'status' => 'Trạng thái hóa đơn',
            'create_time' => 'Thời gian tạo',
            'update_time' => 'Thời gian cập nhật',
            'pay_time' => 'Thời gian thanh toán',
        ],
    ];

    /**
     * @throws Exception
     */
    public function index(ServerRequest $request, Response $response, array $args): ResponseInterface
    {
        return $response->write(
            $this->view()
                ->assign('details', self::$details)
                ->fetch('user/invoice/index.tpl')
        );
    }

    /**
     * @throws Exception
     */
    public function detail(ServerRequest $request, Response $response, array $args): ResponseInterface
    {
        $id = $this->antiXss->xss_clean($args['id']);

        $invoice = (new Invoice())->where('user_id', $this->user->id)->where('id', $id)->first();

        if ($invoice === null) {
            return $response->withRedirect('/user/invoice');
        }

        $paylist = [];

        if ($invoice->status === 'paid_gateway') {
            $paylist = (new Paylist())->where('invoice_id', $invoice->id)->where('status', 1)->first();
        }

        $invoice->status_text = $invoice->status();
        $invoice->create_time = Tools::toDateTime($invoice->create_time);
        $invoice->update_time = Tools::toDateTime($invoice->update_time);
        $invoice->pay_time = Tools::toDateTime($invoice->pay_time);
        $invoice_content = json_decode($invoice->content);

        $payments = Payment::getPaymentsEnabled();
        // Hard guarantee + de-dupe by class name (avoid "\App\..." vs "App\..." duplicates).
        $normalized = [];
        foreach ($payments as $payment) {
            $class = '\\' . ltrim((string) $payment, '\\');
            $normalized[$class] = $class;
        }
        if (ManualQr::_enable()) {
            $manual = '\\' . ltrim(ManualQr::class, '\\');
            $normalized[$manual] = $manual;
        }
        $payments = array_values($normalized);

        return $response->write(
            $this->view()
                ->assign('invoice', $invoice)
                ->assign('invoice_content', $invoice_content)
                ->assign('paylist', $paylist)
                ->assign('payments', $payments)
                ->assign('invoice_price_vnd', Tools::formatVnd((float) $invoice->price, 0, true))
                ->assign('invoice_price_qr', (string) (int) round((float) $invoice->price))
                ->fetch('user/invoice/view.tpl')
        );
    }

    public function payBalance(ServerRequest $request, Response $response, array $args): ResponseInterface
    {
        $invoice_id = $this->antiXss->xss_clean($request->getParam('invoice_id'));

        if ($invoice_id === null || $invoice_id === '') {
            return $response->withJson([
                'ret' => 0,
                'msg' => 'Hóa đơn không tồn tại',
            ]);
        }

        $user = $this->user;

        if ($user->is_shadow_banned) {
            return $response->withJson([
                'ret' => 0,
                'msg' => 'Thanh toán thất bại, vui lòng thử lại sau',
            ]);
        }

        try {
            DB::beginTransaction();

            $invoice = (new Invoice())
                ->where('user_id', $user->id)
                ->where('id', $invoice_id)
                ->lockForUpdate()
                ->first();

            if ($invoice === null) {
                DB::rollBack();

                return $response->withJson([
                    'ret' => 0,
                    'msg' => 'Hóa đơn không tồn tại',
                ]);
            }

            if (! in_array($invoice->status, ['unpaid', 'partially_paid'], true)) {
                DB::rollBack();

                return $response->withJson([
                    'ret' => 0,
                    'msg' => 'Hóa đơn này đã được thanh toán',
                    'redir' => '/user/invoice/' . $invoice->id . '/view',
                ])->withHeader('HX-Redirect', '/user/invoice/' . $invoice->id . '/view');
            }

            if ($invoice->type === 'topup') {
                DB::rollBack();

                return $response->withJson([
                    'ret' => 0,
                    'msg' => 'Hóa đơn này không hỗ trợ thanh toán bằng số dư',
                ]);
            }

            $freshUser = (new User())
                ->where('id', $user->id)
                ->lockForUpdate()
                ->first();

            if ($freshUser === null || (float) $freshUser->money <= 0) {
                DB::rollBack();

                return $response->withJson([
                    'ret' => 0,
                    'msg' => 'Số dư không đủ',
                ]);
            }

            $money_before = (float) $freshUser->money;
            $invoice_price = (float) $invoice->price;

            if ($money_before >= $invoice_price) {
                $paid = $invoice_price;
                $invoice->status = 'paid_balance';
            } else {
                $paid = $money_before;
                $invoice->status = 'partially_paid';
                $invoice->price = $invoice_price - $paid;
                $invoice_content = json_decode($invoice->content);
                if (! is_array($invoice_content)) {
                    $invoice_content = [];
                }
                $invoice_content[] = [
                    'content_id' => count($invoice_content),
                    'name' => 'Thanh toán một phần bằng số dư',
                    'price' => '-' . $paid,
                ];
                $invoice->content = json_encode($invoice_content);
            }

            $freshUser->money = $money_before - $paid;
            $freshUser->save();

            (new UserMoneyLog())->add(
                $freshUser->id,
                $money_before,
                (float) $freshUser->money,
                -$paid,
                'Thanh toán hóa đơn #' . $invoice->id
            );

            $invoice->update_time = time();
            $invoice->pay_time = time();
            $invoice->save();

            DB::commit();

            // Keep in-memory user in sync for this request.
            $this->user->money = $freshUser->money;
        } catch (Exception $e) {
            try {
                DB::rollBack();
            } catch (Exception) {
                // ignore rollback errors
            }

            return $response->withJson([
                'ret' => 0,
                'msg' => 'Thanh toán thất bại, vui lòng thử lại',
            ]);
        }

        if ($invoice->status === 'paid_balance') {
            try {
                $order = (new Order())->find($invoice->order_id);
                if ($order !== null && $order->status === 'pending_payment') {
                    $order->status = 'pending_activation';
                    $order->update_time = time();
                    $order->save();
                }
                CronService::processShopOrdersNow();
            } catch (Exception) {
                // Cron will retry if immediate activation fails.
            }

            return $response->withJson([
                'ret' => 1,
                'msg' => 'Thanh toán thành công',
                'redir' => '/user/invoice',
            ])->withHeader('HX-Redirect', '/user/invoice');
        }

        return $response->withJson([
            'ret' => 1,
            'msg' => 'Đã thanh toán một phần bằng số dư',
            'redir' => '/user/invoice/' . $invoice->id . '/view',
        ])->withHeader('HX-Redirect', '/user/invoice/' . $invoice->id . '/view');
    }

    public function status(ServerRequest $request, Response $response, array $args): ResponseInterface
    {
        $id = $args['id'];
        $invoice = (new Invoice())->where('user_id', $this->user->id)->where('id', $id)->first();

        if ($invoice === null) {
            return $response->withJson([
                'ret' => 0,
                'msg' => 'Hóa đơn không tồn tại',
            ]);
        }

        $paid = in_array($invoice->status, ['paid_gateway', 'paid_balance', 'paid_admin'], true);

        return $response->withJson([
            'ret' => 1,
            'id' => (int) $invoice->id,
            'status' => (string) $invoice->status,
            'status_text' => $invoice->status(),
            'paid' => $paid,
            'update_time' => (int) $invoice->update_time,
            'pay_time' => (int) $invoice->pay_time,
        ]);
    }

    public function ajax(ServerRequest $request, Response $response, array $args): ResponseInterface
    {
        $invoices = (new Invoice())->orderBy('id', 'desc')->where('user_id', $this->user->id)->get();

        foreach ($invoices as $invoice) {
            $invoice->op = '<a class="btn btn-primary" href="/user/invoice/' . $invoice->id . '/view">Xem</a>';
            $invoice->status = $invoice->status();
            $invoice->create_time = Tools::toDateTime($invoice->create_time);
            $invoice->update_time = Tools::toDateTime($invoice->update_time);
            $invoice->pay_time = Tools::toDateTime($invoice->pay_time);
            $invoice->price = Tools::formatVnd((float) $invoice->price, 0);
        }

        return $response->withJson([
            'invoices' => $invoices,
        ]);
    }
}
