<?php

declare(strict_types=1);

namespace App\Services\Gateway;

use App\Models\Config;
use App\Models\Invoice;
use App\Models\Ticket;
use App\Services\Auth;
use App\Services\Notification;
use App\Services\View;
use App\Utils\Tools;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Message\ResponseInterface;
use Slim\Http\Response;
use Slim\Http\ServerRequest;
use Telegram\Bot\Exceptions\TelegramSDKException;
use voku\helper\AntiXSS;
use function json_encode;
use function time;

final class ManualQr extends Base
{
    public function __construct()
    {
        $this->antiXss = new AntiXSS();
    }

    public static function _name(): string
    {
        return 'manualqr';
    }

    public static function _enable(): bool
    {
        if (self::getActiveGateway('manualqr')) {
            return true;
        }

        // Auto-enable when VietQR bank details are already configured.
        $bank_bin = trim((string) Config::obtain('manual_qr_bank_bin'));
        $account_number = trim((string) Config::obtain('manual_qr_account_number'));

        return $bank_bin !== '' && $account_number !== '';
    }

    public static function _readableName(): string
    {
        return 'Chuyển khoản QR thủ công';
    }

    public function purchase(ServerRequest $request, Response $response, array $args): ResponseInterface
    {
        $invoice_id = $this->antiXss->xss_clean($request->getParam('invoice_id'));
        $confirm_paid = $this->antiXss->xss_clean($request->getParam('confirm_paid'));
        $invoice = (new Invoice())->find($invoice_id);

        if ($invoice === null) {
            return $response->withJson([
                'ret' => 0,
                'msg' => 'Invoice not found',
            ]);
        }

        if ($confirm_paid === '1') {
            $user = Auth::getUser();
            $content = [[
                'comment_id' => 0,
                'commenter_type' => 'user',
                'commenter_name' => $user->user_name,
                'comment' => $this->antiXss->xss_clean(
                    "Xác nhận đã chuyển khoản cho hóa đơn #{$invoice->id}." .
                    " Số tiền: " . Tools::formatVnd((float) $invoice->price, 0, true) . '.' .
                    " Nội dung chuyển khoản: INV{$invoice->id}."
                ),
                'datetime' => time(),
            ]];

            $ticket = new Ticket();
            $ticket->title = "Xác nhận chuyển khoản hóa đơn #{$invoice->id}";
            $ticket->content = json_encode($content);
            $ticket->userid = $user->id;
            $ticket->datetime = time();
            $ticket->status = 'open_wait_admin';
            $ticket->type = 'billing';
            $ticket->save();

            if (Config::obtain('mail_ticket')) {
                try {
                    Notification::notifyAdmin(
                        $_ENV['appName'] . '- Xác nhận chuyển khoản mới',
                        'Quản trị viên, có xác nhận chuyển khoản mới cho hóa đơn <strong>#' .
                        $invoice->id . '</strong>. Vui lòng kiểm tra phiếu <a href="' .
                        $_ENV['baseUrl'] . '/admin/ticket/' . $ticket->id . '/view">#' . $ticket->id . '</a>.'
                    );
                } catch (ClientExceptionInterface|GuzzleException|TelegramSDKException) {
                    // Ignore notification errors to avoid blocking user redirect.
                }
            }

            return $response->withHeader('HX-Redirect', '/user');
        }

        return $response->withHeader('HX-Redirect', '/user/invoice/' . $invoice->id . '/view');
    }

    public function notify(ServerRequest $request, Response $response, array $args): ResponseInterface
    {
        return $response->withStatus(404);
    }

    public static function getPurchaseHTML(): string
    {
        return View::getSmarty()->fetch('gateway/manualqr.tpl');
    }
}
