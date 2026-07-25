<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\Product;
use App\Utils\Tools;
use Exception;
use Psr\Http\Message\ResponseInterface;
use Slim\Http\Response;
use Slim\Http\ServerRequest;
use function json_decode;
use function json_encode;
use function time;

final class ProductController extends BaseController
{
    private static array $details = [
        'field' => [
            'op' => 'Thao tác',
            'id' => 'ID sản phẩm',
            'type' => 'Loại',
            'name' => 'Tên',
            'price' => 'Giá bán',
            'status' => 'Trạng thái bán hàng',
            'create_time' => 'Thời gian tạo',
            'update_time' => 'Thời gian cập nhật',
            'sale_count' => 'Doanh số tích lũy',
            'stock' => 'Tồn kho',
        ],
    ];

    private static array $update_field = [
        'type',
        'name',
        'price',
        'status',
        'stock',
        'time',
        'bandwidth',
        'class',
        'class_time',
        'node_group',
        'speed_limit',
        'ip_limit',
        'class_required',
        'node_group_required',
    ];

    private static string $invalid_data_msg = 'Dữ liệu sản phẩm không hợp lệ';

    /**
     * @throws Exception
     */
    public function index(ServerRequest $request, Response $response, array $args): ResponseInterface
    {
        return $response->write(
            $this->view()
                ->assign('details', self::$details)
                ->fetch('admin/product/index.tpl')
        );
    }

    /**
     * @throws Exception
     */
    public function create(ServerRequest $request, Response $response, array $args): ResponseInterface
    {
        return $response->write(
            $this->view()
                ->assign('update_field', self::$update_field)
                ->assign('product_options', [])
                ->assign('product_options_json', '[]')
                ->fetch('admin/product/create.tpl')
        );
    }

    /**
     * @throws Exception
     */
    public function edit(ServerRequest $request, Response $response, array $args): ResponseInterface
    {
        $id = $args['id'];
        $product = (new Product())->find($id);
        $content = json_decode($product->content);
        $limit = json_decode($product->limit);

        $content->time = $content->time ?? 0;
        $content->class = $content->class ?? 0;
        $content->class_time = $content->class_time ?? 0;
        $content->bandwidth = $content->bandwidth ?? 0;
        $content->node_group = $content->node_group ?? 0;
        $content->speed_limit = $content->speed_limit ?? 0;
        $content->ip_limit = $content->ip_limit ?? 0;
        $product_options = Product::normalizeOptions($content);

        return $response->write(
            $this->view()
                ->assign('product', $product)
                ->assign('content', $content)
                ->assign('limit', $limit)
                ->assign('product_options', $product_options)
                ->assign('product_options_json', json_encode($product_options, JSON_UNESCAPED_UNICODE))
                ->assign('update_field', self::$update_field)
                ->fetch('admin/product/edit.tpl')
        );
    }

    public function add(ServerRequest $request, Response $response, array $args): ResponseInterface
    {
        // base product
        $type = $request->getParam('type') ?? '';
        $name = $request->getParam('name') ?? '';
        $price = $request->getParam('price') ?? 0;
        $status = $request->getParam('status') ?? 1;
        $stock = $request->getParam('stock') ?? -1;
        // content
        $time = $request->getParam('time') ?? 0;
        $bandwidth = $request->getParam('bandwidth') ?? 0;
        $class = $request->getParam('class') ?? 0;
        $class_time = $request->getParam('class_time') ?? 0;
        $node_group = $request->getParam('node_group') ?? 0;
        $speed_limit = $request->getParam('speed_limit') ?? 0;
        $ip_limit = $request->getParam('ip_limit') ?? 0;
        // limit
        $class_required = $request->getParam('class_required') ?? '';
        $node_group_required = $request->getParam('node_group_required') ?? '';
        $new_user_required = $request->getParam('new_user_required') === 'true' ? 1 : 0;
        $options = Product::parseOptionsPayload($request->getParam('options_json') ?? '[]');

        $product = new Product();

        if ($options === null) {
            return $response->withJson([
                'ret' => 0,
                'msg' => 'Tùy chọn thời hạn/giá không hợp lệ',
            ]);
        }

        if ($price < 0) {
            return $response->withJson([
                'ret' => 0,
                'msg' => self::$invalid_data_msg,
            ]);
        }

        if ($type === 'tabp') {
            if ($time <= 0 || $class_time <= 0 || $bandwidth <= 0) {
                return $response->withJson([
                    'ret' => 0,
                    'msg' => self::$invalid_data_msg,
                ]);
            }

            $content = [
                'time' => $time,
                'bandwidth' => $bandwidth,
                'class' => $class,
                'class_time' => $class_time,
                'node_group' => $node_group,
                'speed_limit' => $speed_limit,
                'ip_limit' => $ip_limit,
            ];
        } elseif ($type === 'time') {
            if ($time <= 0 || $class_time === '' || $class_time <= 0) {
                return $response->withJson([
                    'ret' => 0,
                    'msg' => self::$invalid_data_msg,
                ]);
            }

            $content = [
                'time' => $time,
                'class' => $class,
                'class_time' => $class_time,
                'node_group' => $node_group,
                'speed_limit' => $speed_limit,
                'ip_limit' => $ip_limit,
            ];
        } elseif ($type === 'bandwidth') {
            if ($bandwidth <= 0) {
                return $response->withJson([
                    'ret' => 0,
                    'msg' => self::$invalid_data_msg,
                ]);
            }

            $content = [
                'bandwidth' => $bandwidth,
            ];
            $options = [];
        } else {
            return $response->withJson([
                'ret' => 0,
                'msg' => self::$invalid_data_msg,
            ]);
        }

        if ($options !== [] && ($type === 'tabp' || $type === 'time')) {
            $content['options'] = $options;
            $content['time'] = $options[0]['days'];
            $content['class_time'] = $options[0]['days'];
            $price = $options[0]['price'];
        }

        $limit = [
            'class_required' => $class_required,
            'node_group_required' => $node_group_required,
            'new_user_required' => $new_user_required,
        ];

        $product->type = $type;
        $product->name = $name;
        $product->price = $price;
        $product->content = json_encode($content);
        $product->limit = json_encode($limit);
        $product->status = $status;
        $product->create_time = time();
        $product->update_time = time();
        $product->sale_count = 0;
        $product->stock = $stock;
        $product->save();

        return $response->withJson([
            'ret' => 1,
            'msg' => 'Thêm thành công',
        ]);
    }

    public function update(ServerRequest $request, Response $response, array $args): ResponseInterface
    {
        $product_id = $args['id'];
        // base product
        $type = (string) ($request->getParam('type') ?? '');
        $name = (string) ($request->getParam('name') ?? '');
        $price = (float) ($request->getParam('price') ?? 0);
        $status = (int) ($request->getParam('status') ?? 1);
        $stock = (int) ($request->getParam('stock') ?? -1);
        // content
        $time = (int) ($request->getParam('time') ?? 0);
        $bandwidth = (float) ($request->getParam('bandwidth') ?? 0);
        $class = (int) ($request->getParam('class') ?? 0);
        $class_time = (int) ($request->getParam('class_time') ?? 0);
        $node_group = (int) ($request->getParam('node_group') ?? 0);
        $speed_limit = (float) ($request->getParam('speed_limit') ?? 0);
        $ip_limit = (int) ($request->getParam('ip_limit') ?? 0);
        // limit
        $class_required = $request->getParam('class_required') ?? '';
        $node_group_required = $request->getParam('node_group_required') ?? '';
        $new_user_required = $request->getParam('new_user_required') === 'true' ? 1 : 0;
        $options = Product::parseOptionsPayload($request->getParam('options_json') ?? '[]');

        $product = (new Product())->find($product_id);

        if ($product === null) {
            return $response->withJson([
                'ret' => 0,
                'msg' => 'Sản phẩm không tồn tại',
            ]);
        }

        if ($options === null) {
            return $response->withJson([
                'ret' => 0,
                'msg' => 'Tùy chọn thời hạn/giá không hợp lệ',
            ]);
        }

        // Apply first option before validation so duration/price stay consistent
        if ($options !== [] && ($type === 'tabp' || $type === 'time')) {
            $time = (int) $options[0]['days'];
            $class_time = (int) $options[0]['days'];
            $price = (float) $options[0]['price'];
        }

        if ($price < 0) {
            return $response->withJson([
                'ret' => 0,
                'msg' => self::$invalid_data_msg,
            ]);
        }

        if ($type === 'tabp') {
            if ($time <= 0 || $class_time <= 0 || $bandwidth <= 0) {
                return $response->withJson([
                    'ret' => 0,
                    'msg' => self::$invalid_data_msg,
                ]);
            }

            $content = [
                'time' => $time,
                'bandwidth' => $bandwidth,
                'class' => $class,
                'class_time' => $class_time,
                'node_group' => $node_group,
                'speed_limit' => $speed_limit,
                'ip_limit' => $ip_limit,
            ];
        } elseif ($type === 'time') {
            if ($time <= 0 || $class_time <= 0) {
                return $response->withJson([
                    'ret' => 0,
                    'msg' => self::$invalid_data_msg,
                ]);
            }

            $content = [
                'time' => $time,
                'class' => $class,
                'class_time' => $class_time,
                'node_group' => $node_group,
                'speed_limit' => $speed_limit,
                'ip_limit' => $ip_limit,
            ];
        } elseif ($type === 'bandwidth') {
            if ($bandwidth <= 0) {
                return $response->withJson([
                    'ret' => 0,
                    'msg' => self::$invalid_data_msg,
                ]);
            }

            $content = [
                'bandwidth' => $bandwidth,
            ];
            $options = [];
        } else {
            return $response->withJson([
                'ret' => 0,
                'msg' => self::$invalid_data_msg,
            ]);
        }

        if ($options !== [] && ($type === 'tabp' || $type === 'time')) {
            $content['options'] = $options;
        }

        $limit = [
            'class_required' => $class_required,
            'node_group_required' => $node_group_required,
            'new_user_required' => $new_user_required,
        ];

        $product->type = $type;
        $product->name = $name;
        $product->price = $price;
        $product->content = json_encode($content, JSON_UNESCAPED_UNICODE);
        $product->limit = json_encode($limit, JSON_UNESCAPED_UNICODE);
        $product->stock = $stock;
        $product->status = $status;
        $product->update_time = time();
        $product->save();

        return $response->withJson([
            'ret' => 1,
            'msg' => 'Cập nhật thành công',
        ]);
    }

    public function delete(ServerRequest $request, Response $response, array $args): ResponseInterface
    {
        $product_id = $args['id'];
        (new Product())->find($product_id)->delete();

        return $response->withJson([
            'ret' => 1,
            'msg' => 'Xóa thành công',
        ]);
    }

    public function copy(ServerRequest $request, Response $response, array $args): ResponseInterface
    {
        $old_product_id = $args['id'];
        $old_product = (new Product())->find($old_product_id);

        $new_product = $old_product->replicate([
            'create_time',
            'update_time',
        ]);
        $new_product->name .= ' (bản sao)';
        $new_product->create_time = time();
        $new_product->update_time = time();
        $new_product->sale_count = 0;
        $new_product->save();

        return $response->withJson([
            'ret' => 1,
            'msg' => 'Sao chép thành công',
        ]);
    }

    public function ajax(ServerRequest $request, Response $response, array $args): ResponseInterface
    {
        $products = (new Product())->orderBy('id', 'desc')->get();

        foreach ($products as $product) {
            $product->op = '<button class="btn btn-red" id="delete-product-' . $product->id . '"
             onclick="deleteProduct(' . $product->id . ')">Xóa</button>
            <button class="btn btn-orange" id="copy-product-' . $product->id . '"
             onclick="copyProduct(' . $product->id . ')">Sao chép</button>
            <a class="btn btn-primary" href="/admin/product/' . $product->id . '/edit">Chỉnh sửa</a>';
            $product->type = $product->type();
            $product->status = $product->status();
            $product->create_time = Tools::toDateTime($product->create_time);
            $product->update_time = Tools::toDateTime($product->update_time);
            $product->stock = $product->stock();
            $product->price = Tools::formatVnd((float) $product->price);
        }

        return $response->withJson([
            'products' => $products,
        ]);
    }
}
