<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\Ann;
use App\Models\Config;
use App\Models\EmailQueue;
use App\Models\User;
use App\Services\Notification;
use App\Utils\Tools;
use Exception;
use GuzzleHttp\Exception\GuzzleException;
use League\HTMLToMarkdown\HtmlConverter;
use Psr\Http\Message\ResponseInterface;
use Slim\Http\Response;
use Slim\Http\ServerRequest;
use Telegram\Bot\Exceptions\TelegramSDKException;
use function in_array;
use function strip_tags;
use function strlen;
use function time;
use const PHP_EOL;

final class AnnController extends BaseController
{
    private static array $details =
        [
            'field' => [
                'op' => 'Thao tác',
                'id' => 'ID',
                'status' => 'Trạng thái',
                'sort' => 'Sắp xếp',
                'date' => 'Ngày',
                'content' => 'Nội dung (trích đoạn)',
            ],
        ];

    private static array $update_field = [
        'status',
        'sort',
    ];

    /**
     * 后台公告页面
     *
     * @throws Exception
     */
    public function index(ServerRequest $request, Response $response, array $args): ResponseInterface
    {
        return $response->write(
            $this->view()
                ->assign('details', self::$details)
                ->fetch('admin/announcement/index.tpl')
        );
    }

    /**
     * 后台公告创建页面
     *
     * @throws Exception
     */
    public function create(ServerRequest $request, Response $response, array $args): ResponseInterface
    {
        return $response->write(
            $this->view()
                ->assign('update_field', self::$update_field)
                ->fetch('admin/announcement/create.tpl')
        );
    }

    /**
     * 后台添加公告
     */
    public function add(ServerRequest $request, Response $response, array $args): ResponseInterface
    {
        $status = (int) $request->getParam('status');
        $sort = (int) $request->getParam('sort');
        $email_notify_class = (int) $request->getParam('email_notify_class');
        $email_notify = $request->getParam('email_notify') === 'true' ? 1 : 0;
        $content = $request->getParam('content');

        if ($content === '') {
            return $response->withJson([
                'ret' => 0,
                'msg' => 'Nội dung không được để trống',
            ]);
        }

        $ann = new Ann();
        $ann->status = in_array($status, [0, 1, 2]) ? $status : 1;
        $ann->sort = $sort > 999 || $sort < 0 ? 0 : $sort;
        $ann->date = Tools::toDateTime(time());
        $ann->content = $content;

        if (! $ann->save()) {
            return $response->withJson([
                'ret' => 0,
                'msg' => 'Lưu thông báo thất bại',
            ]);
        }

        if ($email_notify) {
            $users = (new User())->where('class', '>=', $email_notify_class)
                ->where('is_banned', '=', 0)
                ->get();
            $subject = $_ENV['appName'] . ' - Thông báo mới';

            foreach ($users as $user) {
                (new EmailQueue())->add(
                    $user->email,
                    $subject,
                    'warn.tpl',
                    [
                        'user' => $user,
                        'text' => $content,
                    ]
                );
            }
        }

        if (Config::obtain('im_bot_group_notify_ann_create')) {
            $converter = new HtmlConverter(['strip_tags' => true]);
            $content = $converter->convert($content);

            try {
                Notification::notifyUserGroup('Thông báo mới:' . PHP_EOL . $content);
            } catch (TelegramSDKException | GuzzleException) {
                return $response->withJson([
                    'ret' => 0,
                    'msg' => $email_notify === 1 ? 'Thêm thông báo thành công, gửi email thành công, gửi IM Bot thất bại' : 'Thêm thông báo thành công, gửi IM Bot thất bại',
                ]);
            }
        }

        return $response->withJson([
            'ret' => 1,
            'msg' => $email_notify === 1 ? 'Thêm thông báo thành công, gửi email thành công' : 'Thêm thông báo thành công',
        ]);
    }

    /**
     * 后台Chỉnh sửa公告页面
     *
     * @throws Exception
     */
    public function edit(ServerRequest $request, Response $response, array $args): ResponseInterface
    {
        return $response->write(
            $this->view()
                ->assign('ann', (new Ann())->find($args['id']))
                ->assign('update_field', self::$update_field)
                ->fetch('admin/announcement/edit.tpl')
        );
    }

    /**
     * 后台Chỉnh sửa公告提交
     */
    public function update(ServerRequest $request, Response $response, array $args): ResponseInterface
    {
        $status = (int) $request->getParam('status');
        $sort = (int) $request->getParam('sort');
        $content = $request->getParam('content');

        if ($content === '') {
            return $response->withJson([
                'ret' => 0,
                'msg' => 'Nội dung không được để trống',
            ]);
        }

        $ann = (new Ann())->find($args['id']);

        if ($ann === null) {
            return $response->withJson([
                'ret' => 0,
                'msg' => 'Thông báo không tồn tại',
            ]);
        }

        $ann->status = in_array($status, [0, 1, 2]) ? $status : 1;
        $ann->sort = $sort > 999 || $sort < 0 ? 0 : $sort;
        $ann->content = $content;
        $ann->date = Tools::toDateTime(time());

        if (! $ann->save()) {
            return $response->withJson([
                'ret' => 0,
                'msg' => 'Cập nhật thông báo thất bại',
            ]);
        }

        if (Config::obtain('im_bot_group_notify_ann_update')) {
            $converter = new HtmlConverter(['strip_tags' => true]);
            $content = $converter->convert($ann->content);

            try {
                Notification::notifyUserGroup('Cập nhật thông báo:' . PHP_EOL . $content);
            } catch (TelegramSDKException | GuzzleException) {
                return $response->withJson([
                    'ret' => 0,
                    'msg' => 'Cập nhật thông báo thành công, gửi IM Bot thất bại',
                ]);
            }
        }

        return $response->withJson([
            'ret' => 1,
            'msg' => 'Cập nhật thông báo thành công',
        ]);
    }

    /**
     * 后台Xóa公告
     */
    public function delete(ServerRequest $request, Response $response, array $args): ResponseInterface
    {
        if ((new Ann())->find($args['id'])->delete()) {
            return $response->withJson([
                'ret' => 1,
                'msg' => 'Xóa thành công',
            ]);
        }

        return $response->withJson([
            'ret' => 0,
            'msg' => 'Xóa thất bại',
        ]);
    }

    /**
     * 后台公告页面 AJAX
     */
    public function ajax(ServerRequest $request, Response $response, array $args): ResponseInterface
    {
        $anns = (new Ann())->orderBy('id')->get();

        foreach ($anns as $ann) {
            $ann->op = '<button class="btn btn-red" id="delete-announcement-' . $ann->id . '" 
            onclick="deleteAnn(' . $ann->id . ')">Xóa</button>
            <a class="btn btn-primary" href="/admin/announcement/' . $ann->id . '/edit">Chỉnh sửa</a>';
            $ann->status = $ann->status();
            $ann->content = strlen($ann->content) > 40 ? mb_substr(strip_tags($ann->content), 0, 40, 'UTF-8') . '...' : $ann->content;
        }

        return $response->withJson([
            'anns' => $anns,
        ]);
    }
}
