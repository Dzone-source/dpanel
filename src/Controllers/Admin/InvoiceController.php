<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\Paylist;
use App\Models\User;
use App\Services\Cron as CronService;
use App\Utils\Tools;
use Exception;
use Psr\Http\Message\ResponseInterface;
use Slim\Http\Response;
use Slim\Http\ServerRequest;
use Throwable;
use function in_array;
use function json_decode;
use function time;

final class InvoiceController extends BaseController
{
    private static array $details = [
        'field' => [
            'op' => 'Thao tác',
            'id' => 'ID hóa đơn',
            'user_id' => 'Email người sở hữu',
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
                ->fetch('admin/invoice/index.tpl')
        );
    }

    /**
     * @throws Exception
     */
    public function detail(ServerRequest $request, Response $response, array $args): ResponseInterface
    {
        $id = $args['id'];
        $invoice = (new Invoice())->find($id);
        $paylist = [];

        if ($invoice->status === 'paid_gateway') {
            $paylist = (new Paylist())->where('invoice_id', $invoice->id)->where('status', 1)->first();
        }

        $invoice->status_text = $invoice->status();
        $invoice->create_time = Tools::toDateTime($invoice->create_time);
        $invoice->update_time = Tools::toDateTime($invoice->update_time);
        $invoice->pay_time = Tools::toDateTime($invoice->pay_time);
        $invoice_content = json_decode($invoice->content);
        $owner = (new User())->find($invoice->user_id);

        return $response->write(
            $this->view()
                ->assign('invoice', $invoice)
                ->assign('invoice_content', $invoice_content)
                ->assign('paylist', $paylist)
                ->assign('owner_email', $owner?->email ?? ('#' . $invoice->user_id))
                ->fetch('admin/invoice/view.tpl')
        );
    }

    public function markPaid(ServerRequest $request, Response $response, array $args): ResponseInterface
    {
        $invoice_id = $args['id'];
        $invoice = (new Invoice())->find($invoice_id);

        if ($invoice === null) {
            return $response->withJson([
                'ret' => 0,
                'msg' => 'Hóa đơn không tồn tại',
            ]);
        }

        // A re-submitted confirm (double click, retry after a dropped response)
        // must not look like a failure: the invoice is already where we want it.
        if (in_array($invoice->status, ['paid_admin', 'paid_gateway', 'paid_balance'], true)) {
            return $response->withJson([
                'ret' => 1,
                'msg' => 'Hóa đơn này đã được duyệt trước đó',
            ]);
        }

        if (! in_array($invoice->status, ['unpaid', 'partially_paid'], true)) {
            return $response->withJson([
                'ret' => 0,
                'msg' => 'Không thể duyệt hóa đơn ở trạng thái: ' . $invoice->status(),
            ]);
        }

        $order = (new Order())->find($invoice->order_id);

        if ($order === null) {
            return $response->withJson([
                'ret' => 0,
                'msg' => 'Đơn hàng liên quan không tồn tại',
            ]);
        }

        if ($order->status === 'cancelled') {
            return $response->withJson([
                'ret' => 0,
                'msg' => 'Đơn hàng liên quan đã bị hủy, đánh dấu thất bại',
            ]);
        }

        $order->update_time = time();
        $order->status = 'pending_activation';
        $order->save();

        $invoice->update_time = time();
        $invoice->pay_time = time();
        $invoice->status = 'paid_admin';
        $invoice->save();

        // Activate immediately instead of waiting for the next cron tick. The
        // invoice is already paid at this point, so any activation failure must
        // not turn into a 500 the admin reads as "duyệt thất bại".
        try {
            CronService::processShopOrdersNow();
        } catch (Throwable) {
            // Cron loop will retry activation if immediate processing fails.
        }

        return $response->withJson([
            'ret' => 1,
            'msg' => 'Đã duyệt hóa đơn và kích hoạt đơn hàng',
        ]);
    }

    public function ajax(ServerRequest $request, Response $response, array $args): ResponseInterface
    {
        $invoices = (new Invoice())->orderBy('id', 'desc')->get();
        $emails = (new User())->whereIn('id', $invoices->pluck('user_id')->unique()->filter()->all())
            ->pluck('email', 'id');

        foreach ($invoices as $invoice) {
            $invoice->op = '<a class="btn btn-primary" href="/admin/invoice/' . $invoice->id . '/view">Xem</a>';
            $invoice->status = $invoice->status();
            $invoice->create_time = Tools::toDateTime($invoice->create_time);
            $invoice->update_time = Tools::toDateTime($invoice->update_time);
            $invoice->pay_time = Tools::toDateTime($invoice->pay_time);
            $invoice->price = Tools::formatVnd((float) $invoice->price, 0);
            $invoice->user_id = $emails[$invoice->user_id] ?? ('#' . $invoice->user_id);
        }

        return $response->withJson([
            'invoices' => $invoices,
        ]);
    }
}
