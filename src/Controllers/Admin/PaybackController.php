<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\Payback;
use App\Utils\Tools;
use Exception;
use Psr\Http\Message\ResponseInterface;
use Slim\Http\Response;
use Slim\Http\ServerRequest;

final class PaybackController extends BaseController
{
    private static array $details =
        [
            'field' => [
                'id' => 'ID sự kiện',
                'total' => 'Số tiền gốc',
                'userid' => 'ID người dùng khởi tạo',
                'user_name' => 'Tên người dùng khởi tạo',
                'ref_by' => 'ID người dùng hưởng lợi',
                'ref_user_name' => 'Tên người dùng hưởng lợi',
                'ref_get' => 'Số tiền hưởng lợi',
                'invoice_id' => 'ID hóa đơn',
                'datetime' => 'Thời gian',
            ],
        ];

    /**
     * 后台邀请记录页面
     *
     * @throws Exception
     */
    public function index(ServerRequest $request, Response $response, array $args): ResponseInterface
    {
        return $response->write(
            $this->view()
                ->assign('details', self::$details)
                ->fetch('admin/log/payback.tpl')
        );
    }

    /**
     * 后台登录记录页面 AJAX
     */
    public function ajax(ServerRequest $request, Response $response, array $args): ResponseInterface
    {
        $paybacks = (new Payback())->orderBy('id', 'desc')->get();

        foreach ($paybacks as $payback) {
            $payback->datetime = Tools::toDateTime((int) $payback->datetime);
            $payback->user_name = $payback->getAttributes();
            $payback->ref_user_name = $payback->getAttributes();
        }

        return $response->withJson([
            'paybacks' => $paybacks,
        ]);
    }
}
