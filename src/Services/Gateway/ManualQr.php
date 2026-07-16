<?php

declare(strict_types=1);

namespace App\Services\Gateway;

use App\Models\Invoice;
use App\Services\View;
use Psr\Http\Message\ResponseInterface;
use Slim\Http\Response;
use Slim\Http\ServerRequest;
use voku\helper\AntiXSS;

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
        return self::getActiveGateway('manualqr');
    }

    public static function _readableName(): string
    {
        return 'Chuyển khoản QR thủ công';
    }

    public function purchase(ServerRequest $request, Response $response, array $args): ResponseInterface
    {
        $invoice_id = $this->antiXss->xss_clean($request->getParam('invoice_id'));
        $invoice = (new Invoice())->find($invoice_id);

        if ($invoice === null) {
            return $response->withJson([
                'ret' => 0,
                'msg' => 'Invoice not found',
            ]);
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
