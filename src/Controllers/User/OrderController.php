<?php

declare(strict_types=1);

namespace App\Controllers\User;

use App\Controllers\BaseController;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\Product;
use App\Models\UserCoupon;
use App\Utils\Cookie;
use App\Utils\Tools;
use Exception;
use Psr\Http\Message\ResponseInterface;
use Slim\Http\Response;
use Slim\Http\ServerRequest;
use function explode;
use function in_array;
use function json_decode;
use function json_encode;
use function property_exists;
use function time;

final class OrderController extends BaseController
{
    private static array $details = [
        'field' => [
            'op' => 'Thao tác',
            'id' => 'ID đơn hàng',
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
                ->fetch('user/order/index.tpl')
        );
    }

    /**
     * @throws Exception
     */
    public function create(ServerRequest $request, Response $response, array $args): ResponseInterface
    {
        $product_id = $this->antiXss->xss_clean($request->getQueryParams()['product_id']) ?? null;
        $redir = Cookie::get('redir');

        if ($redir !== '') {
            Cookie::set(['redir' => ''], time() - 1);
        }

        if ($product_id === null || $product_id === '') {
            return $response->withRedirect('/user/product');
        }

        $product = (new Product())->where('id', $product_id)->first();
        $product->type_text = $product->type();
        $product->content = json_decode($product->content);

        return $response->write(
            $this->view()
                ->assign('product', $product)
                ->fetch('user/order/create.tpl')
        );
    }

    /**
     * @throws Exception
     */
    public function detail(ServerRequest $request, Response $response, array $args): ResponseInterface
    {
        $id = $this->antiXss->xss_clean($args['id']);

        $order = (new Order())->where('user_id', $this->user->id)->where('id', $id)->first();

        if ($order === null) {
            return $response->withRedirect('/user/order');
        }

        $order->product_type_text = $order->productType();
        $order->status = $order->status();
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
                ->fetch('user/order/view.tpl')
        );
    }

    public function process(ServerRequest $request, Response $response, array $args): ResponseInterface
    {
        return match ($request->getParam('type')) {
            'product' => $this->product($request, $response, $args),
            'topup' => $this->topup($request, $response, $args),
            default => $response->withJson([
                'ret' => 0,
                'msg' => 'Loại đơn hàng không xác định',
            ]),
        };
    }

    public function product(ServerRequest $request, Response $response, array $args): ResponseInterface
    {
        $coupon_raw = $this->antiXss->xss_clean($request->getParam('coupon'));
        $product_id = $this->antiXss->xss_clean($request->getParam('product_id'));

        $product = (new Product())->find($product_id);

        if ($product === null || $product->stock === 0) {
            return $response->withJson([
                'ret' => 0,
                'msg' => 'Sản phẩm không tồn tại hoặc hết hàng',
            ]);
        }

        $buy_price = $product->price;
        $user = $this->user;

        if ($user->is_shadow_banned) {
            return $response->withJson([
                'ret' => 0,
                'msg' => 'Sản phẩm không tồn tại hoặc hết hàng',
            ]);
        }

        $coupon = null;

        if ($coupon_raw !== '') {
            $coupon = (new UserCoupon())->where('code', $coupon_raw)->first();

            if ($coupon === null || ($coupon->expire_time !== 0 && $coupon->expire_time < time())) {
                return $response->withJson([
                    'ret' => 0,
                    'msg' => 'Mã giảm giá không tồn tại hoặc đã hết hạn',
                ]);
            }

            $coupon_limit = json_decode($coupon->limit);

            if ($coupon_limit->disabled) {
                return $response->withJson([
                    'ret' => 0,
                    'msg' => 'Mã giảm giá đã bị vô hiệu hóa',
                ]);
            }

            if ($coupon_limit->product_id !== '' && ! in_array($product_id, explode(',', $coupon_limit->product_id))) {
                return $response->withJson([
                    'ret' => 0,
                    'msg' => 'Mã giảm giá không áp dụng cho sản phẩm này',
                ]);
            }

            $coupon_use_limit = $coupon_limit->use_time;

            if ($coupon_use_limit > 0) {
                $user_use_count = (new Order())->where('user_id', $user->id)->where('coupon', $coupon->code)->count();
                if ($user_use_count >= $coupon_use_limit) {
                    return $response->withJson([
                        'ret' => 0,
                        'msg' => 'Mã giảm giá đã đạt giới hạn sử dụng',
                    ]);
                }
            }

            if (property_exists($coupon_limit, 'total_use_time')) {
                $coupon_total_use_limit = $coupon_limit->total_use_time;
            } else {
                $coupon_total_use_limit = -1;
            }

            if ($coupon_total_use_limit > 0 && $coupon->use_count >= $coupon_total_use_limit) {
                return $response->withJson([
                    'ret' => 0,
                    'msg' => 'Mã giảm giá đã đạt giới hạn sử dụng',
                ]);
            }

            $content = json_decode($coupon->content);

            if ($content->type === 'percentage') {
                $discount = $product->price * $content->value / 100;
            } else {
                $discount = $content->value;
            }

            $buy_price = $product->price - $discount;
        }

        $product_limit = json_decode($product->limit);

        if ($product_limit->class_required !== '' && $user->class < (int) $product_limit->class_required) {
            return $response->withJson([
                'ret' => 0,
                'msg' => 'Cấp tài khoản của bạn không đủ, không thể mua sản phẩm này',
            ]);
        }

        if ($product_limit->node_group_required !== ''
            && $user->node_group !== (int) $product_limit->node_group_required) {
            return $response->withJson([
                'ret' => 0,
                'msg' => 'Nhóm người dùng của bạn không thể mua sản phẩm này',
            ]);
        }

        if ($product_limit->new_user_required !== 0) {
            $order_count = (new Order())->where('user_id', $user->id)->count();
            if ($order_count > 0) {
                return $response->withJson([
                    'ret' => 0,
                    'msg' => 'Sản phẩm này chỉ dành cho người dùng mới',
                ]);
            }
        }

        $order = new Order();
        $order->user_id = $user->id;
        $order->product_id = $product->id;
        $order->product_type = $product->type;
        $order->product_name = $product->name;
        $order->product_content = $product->content;
        $order->coupon = $coupon_raw;
        $order->price = $buy_price;
        $order->status = $buy_price === 0 ? 'pending_activation' : 'pending_payment';
        $order->create_time = time();
        $order->update_time = time();
        $order->save();

        $invoice_content = [];
        $invoice_content[] = [
            'content_id' => 0,
            'name' => $product->name,
            'price' => $product->price,
        ];

        if ($coupon_raw !== '') {
            $invoice_content[] = [
                'content_id' => 1,
                'name' => 'Mã giảm giá ' . $coupon_raw,
                'price' => '-' . $discount,
            ];
        }

        $invoice = new Invoice();
        $invoice->user_id = $user->id;
        $invoice->order_id = $order->id;
        $invoice->content = json_encode($invoice_content);
        $invoice->price = $buy_price;
        $invoice->status = $buy_price === 0 ? 'paid_gateway' : 'unpaid';
        $invoice->create_time = time();
        $invoice->update_time = time();
        $invoice->pay_time = 0;
        $invoice->type = 'product';
        $invoice->save();

        if ($product->stock > 0) {
            $product->stock -= 1;
        }

        $product->sale_count += 1;
        $product->save();

        if ($coupon_raw !== '') {
            $coupon->use_count += 1;
            $coupon->save();
        }

        return $response->withHeader('HX-Redirect', '/user/invoice/' . $invoice->id . '/view');
    }

    public function topup(ServerRequest $request, Response $response, array $args): ResponseInterface
    {
        $amount = $this->antiXss->xss_clean($request->getParam('amount'));
        $amount = is_numeric($amount) ? round((float) $amount, 2) : null;

        if ($amount === null || $amount <= 0) {
            return $response->withJson([
                'ret' => 0,
                'msg' => 'Số tiền nạp không hợp lệ',
            ]);
        }

        $order = new Order();
        $order->user_id = $this->user->id;
        $order->product_id = 0;
        $order->product_type = 'topup';
        $order->product_name = 'Nạp số dư';
        $order->product_content = json_encode(['amount' => $amount]);
        $order->coupon = '';
        $order->price = $amount;
        $order->status = 'pending_payment';
        $order->create_time = time();
        $order->update_time = time();
        $order->save();

        $invoice_content = [];
        $invoice_content[] = [
            'content_id' => 0,
            'name' => 'Nạp số dư',
            'price' => $amount,
        ];

        $invoice = new Invoice();
        $invoice->user_id = $this->user->id;
        $invoice->order_id = $order->id;
        $invoice->content = json_encode($invoice_content);
        $invoice->price = $amount;
        $invoice->status = 'unpaid';
        $invoice->create_time = time();
        $invoice->update_time = time();
        $invoice->pay_time = 0;
        $invoice->type = 'topup';
        $invoice->save();

        return $response->withHeader('HX-Redirect', '/user/invoice/' . $invoice->id . '/view');
    }

    public function ajax(ServerRequest $request, Response $response, array $args): ResponseInterface
    {
        $orders = (new Order())->orderBy('id', 'desc')->where('user_id', $this->user->id)->get();

        foreach ($orders as $order) {
            $order->op = '<a class="btn btn-primary" href="/user/order/' . $order->id . '/view">Xem</a>';

            if ($order->status === 'pending_payment') {
                $invoice_id = (new Invoice())->where('order_id', $order->id)->first()->id;
                $order->op .= '
                <a class="btn btn-red" href="/user/invoice/' . $invoice_id . '/view">Thanh toán</a>';
            }

            $order->product_type = $order->productType();
            $order->status = $order->status();
            $order->create_time = Tools::toDateTime($order->create_time);
            $order->update_time = Tools::toDateTime($order->update_time);
            $order->price = Tools::formatVnd((float) $order->price, 2);
        }

        return $response->withJson([
            'orders' => $orders,
        ]);
    }
}
