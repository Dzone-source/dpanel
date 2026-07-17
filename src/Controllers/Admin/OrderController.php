<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\Paylist;
use App\Utils\Tools;
use Exception;
use Psr\Http\Message\ResponseInterface;
use Slim\Http\Response;
use Slim\Http\ServerRequest;
use function in_array;
use function json_decode;
use function time;

final class OrderController extends BaseController
{
    private static array $details = [
        'field' => [
            'op' => 'Thao tác',
            'id' => 'ID đơn hàng',
            'user_id' => 'Người dùng gửi',
            'product_id' => 'ID sản phẩm',
            'product_type' => 'Loại sản phẩm',
            'product_name' => 'Tên sản phẩm',
            'coupon' => 'Mã giảm giá',
            'price' => 'Số tiền',
            'status' => 'Trạng thái',
            'create_time' => 'Thời gian tạo',
            'update_time' => 'Thời gian cập nhật',
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
                ->fetch('admin/order/index.tpl')
        );
    }

    public function search(ServerRequest $request, Response $response, array $args): ResponseInterface
    {
        $out_order_id = $request->getParam('gateway_order_id');
        $paylist = (new Paylist())->where('tradeno', $out_order_id)->first();
        $invoice = (new Invoice())->where('id', $paylist?->invoice_id)->first();
        $order = (new Order())->where('id', $invoice?->order_id)->first();

        if ($order === null) {
            return $response->withJson([
                'ret' => 0,
                'msg' => 'Không tìm thấy đơn hàng',
            ]);
        }

        return $response->withHeader('HX-Redirect', '/admin/order/' . $order->id . '/view')->withJson([
            'ret' => 1,
            'msg' => 'Đã tìm thấy đơn hàng',
        ]);
    }

    /**
     * @throws Exception
     */
    public function detail(ServerRequest $request, Response $response, array $args): ResponseInterface
    {
        $id = $args['id'];
        $order = (new Order())->find($id);

        if ($order === null) {
            return $response->withStatus(301)->withHeader('Location', '/admin/order');
        }

        $order->product_type_text = $order->productType();
        $order->status_text = $order->status();
        $order->create_time = Tools::toDateTime($order->create_time);
        $order->update_time = Tools::toDateTime($order->update_time);
        $order->content = json_decode($order->product_content);

        $invoice = (new Invoice())->where('order_id', $id)->first();
        $invoice->status = $invoice->status();
        $invoice->create_time = Tools::toDateTime($invoice->create_time);
        $invoice->update_time = Tools::toDateTime($invoice->update_time);
        $invoice->pay_time = Tools::toDateTime($invoice->pay_time);
        $invoice->content = json_decode($invoice->content);

        return $response->write(
            $this->view()
                ->assign('order', $order)
                ->assign('invoice', $invoice)
                ->fetch('admin/order/view.tpl')
        );
    }

    public function cancel(ServerRequest $request, Response $response, array $args): ResponseInterface
    {
        $order_id = $args['id'];
        $order = (new Order())->find($order_id);

        if ($order === null) {
            return $response->withJson([
                'ret' => 0,
                'msg' => 'Đơn hàng không tồn tại',
            ]);
        }

        if (in_array($order->status, ['activated', 'expired', 'cancelled'])) {
            return $response->withJson([
                'ret' => 0,
                'msg' => 'Không thể hủy ' . $order->status() . ' sản phẩm ở trạng thái này',
            ]);
        }

        $invoice = (new Invoice())->where('order_id', $order_id)->first();

        if ($invoice === null) {
            return $response->withJson([
                'ret' => 0,
                'msg' => 'Hóa đơn liên quan không tồn tại',
            ]);
        }

        if ($invoice->status === 'partially_paid') {
            return $response->withJson([
                'ret' => 0,
                'msg' => 'Không thể hủy đơn hàng có hóa đơn đã thanh toán một phần',
            ]);
        }

        $order->update_time = time();
        $order->status = 'cancelled';
        $order->save();

        if (in_array($invoice->status, ['paid_gateway', 'paid_balance', 'paid_admin'])) {
            $invoice->refundToBalance();

            return $response->withJson([
                'ret' => 1,
                'msg' => 'Hủy đơn hàng thành công, hóa đơn liên quan đã hoàn tiền vào số dư',
            ]);
        }

        $invoice->update_time = time();
        $invoice->status = 'cancelled';
        $invoice->save();

        return $response->withJson([
            'ret' => 1,
            'msg' => 'Hủy đơn hàng thành công',
        ]);
    }

    public function delete(ServerRequest $request, Response $response, array $args): ResponseInterface
    {
        $order_id = $args['id'];
        $order = (new Order())->find($order_id);

        if ($order === null) {
            return $response->withJson([
                'ret' => 0,
                'msg' => 'Đơn hàng không tồn tại',
            ]);
        }

        $invoice = (new Invoice())->where('order_id', $order_id)->first();

        if ($order->delete() && $invoice->delete()) {
            return $response->withJson([
                'ret' => 1,
                'msg' => 'Xóa thành công',
            ]);
        }

        return $response->withJson([
            'ret' => 1,
            'msg' => 'Xóa thất bại',
        ]);
    }

    public function ajax(ServerRequest $request, Response $response, array $args): ResponseInterface
    {
        $orders = (new Order())->orderBy('id', 'desc')->get();

        foreach ($orders as $order) {
            $order->op = '<button class="btn btn-red" id="delete-order-' . $order->id . '"
             onclick="deleteOrder(' . $order->id . ')">Xóa</button>';

            if (in_array($order->status, ['pending_payment', 'pending_activation'])) {
                $order->op .= '
                <button class="btn btn-orange" id="cancel-order-' . $order->id . '"
                 onclick="cancelOrder(' . $order->id . ')">Hủy</button>';
            }

            $order->op .= '
            <a class="btn btn-primary" href="/admin/order/' . $order->id . '/view">Xem</a>';
            $order->product_type = $order->productType();
            $order->status = $order->status();
            $order->create_time = Tools::toDateTime($order->create_time);
            $order->update_time = Tools::toDateTime($order->update_time);
            $order->price = Tools::formatVnd((float) $order->price, 0);
        }

        return $response->withJson([
            'orders' => $orders,
        ]);
    }
}
