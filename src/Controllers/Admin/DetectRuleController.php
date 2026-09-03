<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\DetectRule;
use Exception;
use Psr\Http\Message\ResponseInterface;
use Slim\Http\Response;
use Slim\Http\ServerRequest;

final class DetectRuleController extends BaseController
{
    private static array $details =
        [
            'field' => [
                'op' => 'Thao tác',
                'id' => 'ID quy tắc',
                'name' => 'Tên quy tắc',
                'text' => 'Giới thiệu quy tắc',
                'regex' => 'Biểu thức chính quy',
                'type' => 'Loại quy tắc',
            ],
            'add_dialog' => [
                [
                    'id' => 'name',
                    'info' => 'Tên quy tắc',
                    'type' => 'input',
                    'placeholder' => 'Tên quy tắc kiểm toán',
                ],
                [
                    'id' => 'text',
                    'info' => 'Giới thiệu quy tắc',
                    'type' => 'input',
                    'placeholder' => 'Mô tả quy tắc kiểm toán một cách ngắn gọn và rõ ràng',
                ],
                [
                    'id' => 'regex',
                    'info' => 'Biểu thức chính quy',
                    'type' => 'input',
                    'placeholder' => 'Biểu thức chính quy để khớp nội dung kiểm toán',
                ],
                [
                    'id' => 'type',
                    'info' => 'Loại quy tắc',
                    'type' => 'select',
                    'select' => [
                        '1' => 'Khớp gói dữ liệu dạng văn bản thuần',
                        '0' => 'Khớp gói dữ liệu dạng thập lục phân',
                    ],
                ],
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
                ->fetch('admin/detect.tpl')
        );
    }

    public function add(ServerRequest $request, Response $response, array $args): ResponseInterface
    {
        $rule = new DetectRule();
        $rule->name = $request->getParam('name');
        $rule->text = $request->getParam('text');
        $rule->regex = $request->getParam('regex');
        $rule->type = $request->getParam('type');

        if (! $rule->save()) {
            return $response->withJson([
                'ret' => 0,
                'msg' => 'Thêm thất bại',
            ]);
        }

        return $response->withJson([
            'ret' => 1,
            'msg' => 'Thêm thành công',
        ]);
    }

    public function delete(ServerRequest $request, Response $response, array $args): ResponseInterface
    {
        $id = $args['id'];
        $rule = (new DetectRule())->find($id);

        if (! $rule->delete()) {
            return $response->withJson([
                'ret' => 0,
                'msg' => 'Xóa thất bại',
            ]);
        }

        return $response->withJson([
            'ret' => 1,
            'msg' => 'Xóa thành công',
        ]);
    }

    public function ajax(ServerRequest $request, Response $response, array $args): ResponseInterface
    {
        $rules = (new DetectRule())->orderBy('id', 'desc')->get();

        foreach ($rules as $rule) {
            $rule->op = '<button class="btn btn-red" id="delete-rule-' . $rule->id .
                '" onclick="deleteRule(' . $rule->id . ')">Xóa</button>';
            $rule->type = $rule->type();
        }

        return $response->withJson([
            'rules' => $rules,
        ]);
    }
}
