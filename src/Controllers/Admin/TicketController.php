<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\Ticket;
use App\Models\User;
use App\Services\LLM;
use App\Services\Notification;
use App\Utils\ResponseHelper;
use App\Utils\Tools;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Message\ResponseInterface;
use Slim\Http\Response;
use Slim\Http\ServerRequest;
use Smarty\Exception;
use Telegram\Bot\Exceptions\TelegramSDKException;
use function array_merge;
use function count;
use function json_decode;
use function json_encode;
use function nl2br;
use function time;

final class TicketController extends BaseController
{
    private static array $details =
        [
            'field' => [
                'op' => 'Thao tác',
                'id' => 'ID phiếu hỗ trợ',
                'title' => 'Chủ đề',
                'status' => 'Trạng thái phiếu hỗ trợ',
                'type' => 'Loại phiếu hỗ trợ',
                'userid' => 'Người dùng gửi',
                'datetime' => 'Thời gian tạo',
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
                ->fetch('admin/ticket/index.tpl')
        );
    }

    public function reply(ServerRequest $request, Response $response, array $args): ResponseInterface
    {
        $id = $args['id'];
        $comment = $request->getParam('comment') ?? '';

        if ($comment === '') {
            return ResponseHelper::error($response, 'Vui lòng nhập nội dung bình luận');
        }

        $ticket = (new Ticket())->where('id', $id)->first();

        if ($ticket === null) {
            return ResponseHelper::error($response, 'Phiếu hỗ trợ không tồn tại');
        }

        $content_old = json_decode($ticket->content, true);
        $content_new = [
            [
                'comment_id' => $content_old[count($content_old) - 1]['comment_id'] + 1,
                'commenter_type' => 'admin',
                'commenter_name' => 'Admin',
                'comment' => $this->antiXss->xss_clean($comment),
                'datetime' => time(),
            ],
        ];

        $ticket->content = json_encode(array_merge($content_old, $content_new));
        $ticket->status = 'open_wait_user';
        $ticket->save();

        try {
            Notification::notifyUser(
                (new User())->find($ticket->userid),
                $_ENV['appName'] . '- Phiếu hỗ trợ được trả lời',
                'Xin chào, có người đã trả lời<a href="' . $_ENV['baseUrl'] . '/user/ticket/' . $ticket->id . '/view">phiếu hỗ trợ</a>, vui lòng xem.'
            );
        } catch (TelegramSDKException|GuzzleException|ClientExceptionInterface $e) {
            return $response->withHeader('HX-Refresh', 'true');
        }

        return $response->withHeader('HX-Refresh', 'true');
    }

    public function llmReply(ServerRequest $request, Response $response, array $args): ResponseInterface
    {
        $id = $args['id'];
        $ticket = (new Ticket())->where('id', $id)->first();

        if ($ticket === null) {
            return ResponseHelper::error($response, 'Phiếu hỗ trợ không tồn tại');
        }

        $content_old = json_decode($ticket->content, true);

        if (count($content_old) === 1) {
            $context = [
                [
                    'role' => 'user',
                    'content' => $ticket->title,
                ],
                [
                    'role' => 'user',
                    'content' => $content_old[0]['comment'],
                ],
            ];
        } else {
            $context = [
                [
                    'role' => 'user',
                    'content' => $ticket->title,
                ],
            ];

            foreach ($content_old as $comment) {
                $context[] = [
                    'role' => $comment['commenter_type'] ?? $comment['commenter_name'] === 'Admin' ? 'admin' : 'user',
                    'content' => $comment['comment'],
                ];
            }
        }

        $llm_response = LLM::genTextResponseWithContext($context);

        $content_new = [
            [
                'comment_id' => $content_old[count($content_old) - 1]['comment_id'] + 1,
                'commenter_type' => 'llm',
                'commenter_name' => 'AI Assistant',
                'comment' => $this->antiXss->xss_clean($llm_response),
                'datetime' => time(),
            ],
        ];

        $ticket->content = json_encode(array_merge($content_old, $content_new));
        $ticket->status = 'open_wait_user';
        $ticket->save();

        try {
            Notification::notifyUser(
                (new User())->find($ticket->userid),
                $_ENV['appName'] . '- Phiếu hỗ trợ được trả lời',
                'Xin chào, trợ lý AI đã trả lời<a href="' . $_ENV['baseUrl'] . '/user/ticket/' . $ticket->id . '/view">phiếu hỗ trợ</a>, vui lòng xem.'
            );
        } catch (TelegramSDKException|GuzzleException|ClientExceptionInterface $e) {
            return $response->withHeader('HX-Refresh', 'true');
        }

        return $response->withHeader('HX-Refresh', 'true');
    }

    /**
     * 后台Xem指定工单
     *
     * @throws Exception
     */
    public function detail(ServerRequest $request, Response $response, array $args): ResponseInterface
    {
        $id = $args['id'];
        $ticket = (new Ticket())->where('id', '=', $id)->first();

        if ($ticket === null) {
            return $response->withRedirect('/admin/ticket');
        }

        $comments = json_decode($ticket->content);

        foreach ($comments as $comment) {
            $comment->comment = nl2br($comment->comment);
            $comment->datetime = Tools::toDateTime((int) $comment->datetime);
        }

        return $response->write(
            $this->view()
                ->assign('ticket', $ticket)
                ->assign('comments', $comments)
                ->fetch('admin/ticket/view.tpl')
        );
    }

    /**
     * 后台Đóng工单
     */
    public function close(ServerRequest $request, Response $response, array $args): ResponseInterface
    {
        $id = $args['id'];
        $ticket = (new Ticket())->where('id', '=', $id)->first();

        if ($ticket === null) {
            return ResponseHelper::error($response, 'Phiếu hỗ trợ không tồn tại');
        }

        if ($ticket->status === 'closed') {
            return ResponseHelper::error($response, 'Phiếu hỗ trợ đã đóng, không cần thao tác lại');
        }

        $ticket->status = 'closed';
        $ticket->save();

        return ResponseHelper::success($response, 'Đóng phiếu hỗ trợ thành công');
    }

    /**
     * 后台Xóa工单
     */
    public function delete(ServerRequest $request, Response $response, array $args): ResponseInterface
    {
        $id = $args['id'];
        (new Ticket())->where('id', '=', $id)->delete();

        return ResponseHelper::success($response, 'Xóa phiếu hỗ trợ thành công');
    }

    /**
     * 后台工单页面 Ajax
     */
    public function ajax(ServerRequest $request, Response $response, array $args): ResponseInterface
    {
        $tickets = (new Ticket())->orderBy('id', 'desc')->get();

        foreach ($tickets as $ticket) {
            $ticket->op = '<button class="btn btn-red" id="delete-ticket" 
            onclick="deleteTicket(' . $ticket->id . ')">Xóa</button>';

            if ($ticket->status !== 'closed') {
                $ticket->op .= '
                <button class="btn btn-orange" id="close-ticket" 
                onclick="closeTicket(' . $ticket->id . ')">Đóng</button>';
            }

            $ticket->op .= '
            <a class="btn btn-primary" href="/admin/ticket/' . $ticket->id . '/view">Xem</a>';
            $ticket->status = $ticket->status();
            $ticket->type = $ticket->type();
            $ticket->datetime = Tools::toDateTime((int) $ticket->datetime);
        }

        return $response->withJson([
            'tickets' => $tickets,
        ]);
    }
}
