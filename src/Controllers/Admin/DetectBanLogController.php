<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\DetectBanLog;
use App\Utils\Tools;
use Exception;
use Psr\Http\Message\ResponseInterface;
use Slim\Http\Response;
use Slim\Http\ServerRequest;

final class DetectBanLogController extends BaseController
{
    private static array $details =
        [
            'field' => [
                'id' => 'ID sự kiện',
                'user_id' => 'ID người dùng',
                'detect_number' => 'Số lần vi phạm',
                'ban_time' => 'Thời gian cấm (phút)',
                'start_time' => 'Thời gian bắt đầu thống kê',
                'end_time' => 'Kết thúc thống kê & thời gian bắt đầu cấm',
                'ban_end_time' => 'Thời gian kết thúc cấm',
                'all_detect_number' => 'Số lần vi phạm tích lũy',
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
                ->fetch('admin/log/detect_ban.tpl')
        );
    }

    public function ajax(ServerRequest $request, Response $response, array $args): ResponseInterface
    {
        $length = $request->getParam('length');
        $page = $request->getParam('start') / $length + 1;
        $draw = $request->getParam('draw');

        $detect_ban_log = DetectBanLog::query();

        $search = $request->getParam('search')['value'];

        if ($search !== '') {
            $detect_ban_log->where('user_id', '=', $search);
        }

        $order = $request->getParam('order')[0]['dir'];

        if ($request->getParam('order')[0]['column'] !== '0') {
            $order_field = self::$details['field'][$request->getParam('order')[0]['column']];

            $detect_ban_log->orderBy($order_field, $order)->orderBy('id', 'desc');
        } else {
            $detect_ban_log->orderBy('id', $order);
        }

        $filtered = $detect_ban_log->count();
        $total = (new DetectBanLog())->count();

        $bans = $detect_ban_log->paginate($length, '*', '', $page);

        foreach ($bans as $ban) {
            $ban->start_time = Tools::toDateTime((int) $ban->start_time);
            $ban->end_time = Tools::toDateTime((int) $ban->end_time);
            $ban->ban_end_time = $ban->banEndTime();
        }

        return $response->withJson([
            'draw' => $draw,
            'recordsTotal' => $total,
            'recordsFiltered' => $filtered,
            'bans' => $bans,
        ]);
    }
}
