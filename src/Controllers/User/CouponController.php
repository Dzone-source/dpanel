<?php

declare(strict_types=1);

namespace App\Controllers\User;

use App\Controllers\BaseController;
use App\Models\Order;
use App\Models\Product;
use App\Models\UserCoupon;
use Psr\Http\Message\ResponseInterface;
use Slim\Http\Response;
use Slim\Http\ServerRequest;
use function explode;
use function in_array;
use function json_decode;
use function time;

final class CouponController extends BaseController
{
    public function check(ServerRequest $request, Response $response, array $args): ResponseInterface
    {
        $coupon_raw = $this->antiXss->xss_clean($request->getParam('coupon'));
        $product_id = $this->antiXss->xss_clean($request->getParam('product_id'));
        $option_index_raw = $this->antiXss->xss_clean($request->getParam('option_index'));
        $option_index = ($option_index_raw === null || $option_index_raw === '')
            ? null
            : (int) $option_index_raw;
        $invalid_coupon_msg = '优惠码无效';

        if ($coupon_raw === '') {
            return $response->withJson([
                'ret' => 0,
                'msg' => $invalid_coupon_msg,
            ]);
        }

        $coupon = (new UserCoupon())->where('code', $coupon_raw)->first();

        if ($coupon === null || ($coupon->expire_time !== 0 && $coupon->expire_time < time())) {
            return $response->withJson([
                'ret' => 0,
                'msg' => $invalid_coupon_msg,
            ]);
        }

        $product = (new Product())->where('id', $product_id)->first();

        if ($product === null) {
            return $response->withJson([
                'ret' => 0,
                'msg' => $invalid_coupon_msg,
            ]);
        }

        $resolved = $product->resolvePurchaseOption($option_index);
        if ($resolved === null) {
            return $response->withJson([
                'ret' => 0,
                'msg' => 'Vui lòng chọn thời hạn gói hợp lệ',
            ]);
        }

        $base_price = $resolved['price'];

        $limit = json_decode($coupon->limit);

        if ($limit->disabled) {
            return $response->withJson([
                'ret' => 0,
                'msg' => $invalid_coupon_msg,
            ]);
        }

        if ($limit->product_id !== '' && ! in_array($product_id, explode(',', $limit->product_id))) {
            return $response->withJson([
                'ret' => 0,
                'msg' => $invalid_coupon_msg,
            ]);
        }

        $user = $this->user;
        $use_limit = $limit->use_time;

        if ($use_limit > 0) {
            $user_use_count = (new Order())->where('user_id', $user->id)->where('coupon', $coupon->code)->count();
            if ($user_use_count >= $use_limit) {
                return $response->withJson([
                    'ret' => 0,
                    'msg' => $invalid_coupon_msg,
                ]);
            }
        }

        $total_use_limit = $limit->total_use_time;

        if ($total_use_limit > 0 && $coupon->use_count >= $total_use_limit) {
            return $response->withJson([
                'ret' => 0,
                'msg' => $invalid_coupon_msg,
            ]);
        }

        $content = json_decode($coupon->content);

        if ($content->type === 'percentage') {
            $discount = $base_price * $content->value / 100;
        } else {
            $discount = $content->value;
        }

        $buy_price = $base_price - $discount;
        if ($buy_price < 0) {
            $buy_price = 0;
        }

        return $response->withJson([
            'ret' => 1,
            'msg' => '优惠码可用',
            'data' => [
                'coupon-code' => $coupon->code,
                'product-buy-discount' => $discount,
                'product-buy-total' => $buy_price,
            ],
        ]);
    }
}
