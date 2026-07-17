<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\Paylist;
use App\Utils\Tools;
use Exception;
use Psr\Http\Message\ResponseInterface;
use Slim\Http\Response;
use Slim\Http\ServerRequest;

final class PaylistController extends BaseController
{
    private static array $details =
        [
            'field' => [
                'id' => 'ID sự kiện',
                'userid' => 'ID người dùng',
                'total' => 'Số tiền',
                'status' => 'Trạng thái',
                'gateway' => 'Cổng thanh toán',
                'tradeno' => 'Mã giao dịch cổng',
                'datetime' => 'Thời gian thanh toán',
                'invoice_id' => 'ID hóa đơn liên quan',
            ],
        ];

    /**
     * 后台网关记录页面
     *
     * @throws Exception
     */
    public function index(ServerRequest $request, Response $response, array $args): ResponseInterface
    {
        return $response->write(
            $this->view()
                ->assign('details', self::$details)
                ->fetch('admin/log/gateway.tpl')
        );
    }

    /**
     * 后台网关记录页面 AJAX
     */
    public function ajax(ServerRequest $request, Response $response, array $args): ResponseInterface
    {
        $paylists = (new Paylist())->orderBy('id', 'desc')->get();

        foreach ($paylists as $paylist) {
            $paylist->status = $paylist->status();
            $paylist->datetime = Tools::toDateTime((int) $paylist->datetime);
            $paylist->total = Tools::formatVnd((float) $paylist->total);
        }

        return $response->withJson([
            'paylists' => $paylists,
        ]);
    }
}
