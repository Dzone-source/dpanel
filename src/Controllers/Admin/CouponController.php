<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\UserCoupon;
use App\Utils\Tools;
use Exception;
use Psr\Http\Message\ResponseInterface;
use Slim\Http\Response;
use Slim\Http\ServerRequest;
use function in_array;
use function json_decode;
use function json_encode;
use function property_exists;
use function time;

final class CouponController extends BaseController
{
    private static array $details = [
        'field' => [
            'op' => 'Thao tác',
            'id' => 'ID',
            'code' => 'Mã giảm giá',
            'type' => 'Loại',
            'value' => 'Giá trị',
            'product_id' => 'ID sản phẩm khả dụng',
            'use_time' => 'Số lần sử dụng (mỗi người dùng)',
            'total_use_time' => 'Số lần sử dụng (tích lũy)',
            'new_user' => 'Chỉ dành cho người dùng mới',
            'disabled' => 'Đã vô hiệu hóa',
            'use_count' => 'Tổng số lần sử dụng',
            'create_time' => 'Thời gian tạo',
            'expire_time' => 'Thời gian hết hạn',
        ],
        'create_dialog' => [
            [
                'id' => 'code',
                'info' => 'Mã giảm giá',
                'type' => 'input',
                'placeholder' => '',
            ],
            [
                'id' => 'type',
                'info' => 'Loại mã giảm giá',
                'type' => 'select',
                'select' => [
                    'percentage' => 'Phần trăm',
                    'fixed' => 'Số tiền cố định',
                ],
            ],
            [
                'id' => 'value',
                'info' => 'Giá trị mã giảm giá',
                'type' => 'input',
                'placeholder' => '',
            ],
            [
                'id' => 'product_id',
                'info' => 'ID sản phẩm khả dụng (nhiều ID phân cách bằng dấu phẩy)',
                'type' => 'input',
                'placeholder' => '',
            ],
            [
                'id' => 'use_time',
                'info' => 'Giới hạn số lần sử dụng mỗi người dùng (nhỏ hơn 0 là không giới hạn)',
                'type' => 'input',
                'placeholder' => '-1',
            ],
            [
                'id' => 'total_use_time',
                'info' => 'Giới hạn số lần sử dụng tích lũy (nhỏ hơn 0 là không giới hạn)',
                'type' => 'input',
                'placeholder' => '-1',
            ],
            [
                'id' => 'new_user',
                'info' => 'Chỉ dành cho người dùng mới',
                'type' => 'select',
                'select' => [
                    '1' => 'Bật',
                    '0' => 'Vô hiệu hóa',
                ],
            ],
            [
                'id' => 'generate_method',
                'info' => 'Phương thức tạo',
                'type' => 'select',
                'select' => [
                    'char' => 'Ký tự chỉ định',
                    'random' => 'Ký tự ngẫu nhiên (bỏ qua tham số mã giảm giá)',
                    'char_random' => 'Ký tự chỉ định + ký tự ngẫu nhiên',
                ],
            ],
        ],
    ];

    /**
     * 后台Mã giảm giá页面
     *
     * @throws Exception
     */
    public function index(ServerRequest $request, Response $response, array $args): ResponseInterface
    {
        return $response->write(
            $this->view()
                ->assign('details', self::$details)
                ->fetch('admin/coupon.tpl')
        );
    }

    /**
     * 添加Mã giảm giá
     */
    public function add(ServerRequest $request, Response $response, array $args): ResponseInterface
    {
        $code = $request->getParam('code');
        $type = $request->getParam('type');
        $value = $request->getParam('value');
        $product_id = $request->getParam('product_id');
        $use_time = $request->getParam('use_time');
        $total_use_time = $request->getParam('total_use_time');
        $new_user = $request->getParam('new_user');
        $generate_method = $request->getParam('generate_method');
        $expire_time = $request->getParam('expire_time');

        if ($code === '' && in_array($generate_method, ['char', 'char_ramdom'])) {
            return $response->withJson([
                'ret' => 0,
                'msg' => 'Mã giảm giá không được để trống',
            ]);
        }

        if ($type === '' || $value === '' || ($expire_time !== '' && $expire_time < time())) {
            return $response->withJson([
                'ret' => 0,
                'msg' => 'Tham số mã giảm giá không hợp lệ',
            ]);
        }

        if ($generate_method === 'char' && (new UserCoupon())->where('code', $code)->count() !== 0) {
            return $response->withJson([
                'ret' => 0,
                'msg' => 'Mã giảm giá đã tồn tại',
            ]);
        }

        if ($generate_method === 'char_random') {
            $code .= Tools::genRandomChar();

            if ((new UserCoupon())->where('code', $code)->count() !== 0) {
                return $response->withJson([
                    'ret' => 0,
                    'msg' => 'Đã xảy ra sự cố, vui lòng thử lại sau',
                ]);
            }
        }

        if ($generate_method === 'random') {
            $code = Tools::genRandomChar();

            if ((new UserCoupon())->where('code', $code)->count() !== 0) {
                return $response->withJson([
                    'ret' => 0,
                    'msg' => 'Đã xảy ra sự cố, vui lòng thử lại sau',
                ]);
            }
        }

        $content = [
            'type' => $type,
            'value' => $value,
        ];

        $limit = [
            'product_id' => $product_id,
            'use_time' => $use_time,
            'total_use_time' => $total_use_time,
            'new_user' => $new_user,
            'disabled' => 0,
        ];

        $coupon = new UserCoupon();
        $coupon->code = $code;
        $coupon->content = json_encode($content);
        $coupon->limit = json_encode($limit);
        $coupon->create_time = time();

        if ($expire_time !== '') {
            $coupon->expire_time = $expire_time;
        } else {
            $coupon->expire_time = 0;
        }

        $coupon->save();

        return $response->withJson([
            'ret' => 1,
            'msg' => 'Mã giảm giá ' . $code . ' thêm thành công',
        ]);
    }

    public function delete(ServerRequest $request, Response $response, array $args): ResponseInterface
    {
        $coupon_id = $args['id'];
        (new UserCoupon())->find($coupon_id)->delete();

        return $response->withJson([
            'ret' => 1,
            'msg' => 'Xóa thành công',
        ]);
    }

    public function disable(ServerRequest $request, Response $response, array $args): ResponseInterface
    {
        $coupon_id = $args['id'];
        $coupon = (new UserCoupon())->find($coupon_id)->first();
        $limit = json_decode($coupon->limit);
        $limit->disabled = 1;
        $coupon->limit = json_encode($limit);
        $coupon->save();

        return $response->withJson([
            'ret' => 1,
            'msg' => 'Vô hiệu hóa thành công',
        ]);
    }

    /**
     * 后台商品Mã giảm giá页面 AJAX
     */
    public function ajax(ServerRequest $request, Response $response, array $args): ResponseInterface
    {
        $coupons = (new UserCoupon())->orderBy('id', 'desc')->get();

        foreach ($coupons as $coupon) {
            $content = json_decode($coupon->content);
            $limit = json_decode($coupon->limit);

            $coupon->op = '<button class="btn btn-red" id="delete-coupon-' . $coupon->id . '"
                onclick="deleteCoupon(' . $coupon->id . ')">Xóa</button>' .
                ($limit->disabled !== 1 ? '
                <button class="btn btn-orange" id="disable-coupon-' .
                    $coupon->id . '" onclick="disableCoupon(' . $coupon->id . ')">Vô hiệu hóa</button>' : '');

            $coupon->type = $coupon->type();
            $coupon->value = $content->value;
            $coupon->product_id = $limit->product_id;
            $coupon->use_time = (int) $limit->use_time < 0 ? 'Không giới hạn' : $limit->use_time;
            $coupon->total_use_time = ! property_exists($limit, 'total_use_time') ||
            (int) $limit->total_use_time < 0 ? 'Không giới hạn' : $limit->total_use_time;
            $coupon->new_user = $limit->new_user === 1 ? 'Có' : 'Không';
            $coupon->disabled = $limit->disabled === 1 ? 'Có' : 'Không';
            $coupon->create_time = Tools::toDateTime((int) $coupon->create_time);
            $coupon->expire_time = $coupon->expire_time === 0 ? 'Vĩnh viễn' : Tools::toDateTime((int) $coupon->expire_time);
        }

        return $response->withJson([
            'coupons' => $coupons,
        ]);
    }
}
