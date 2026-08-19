<?php

declare(strict_types=1);

namespace App\Services\Gateway;

use App\Models\Config;
use App\Models\Invoice;
use App\Models\Paylist;
use App\Services\Auth;
use App\Services\Gateway\Cryptomus\Payment as CryptomusPayment;
use App\Services\View;
use Exception;
use Psr\Http\Message\ResponseInterface;
use Slim\Http\Response;
use Slim\Http\ServerRequest;
use voku\helper\AntiXSS;
use function json_decode;
use function trim;

final class Cryptomus extends Base
{
    protected array $cryptomus = [];

    public function __construct()
    {
        $this->antiXss = new AntiXSS();

        $this->cryptomus['cryptomus_api_key'] = Config::obtain('cryptomus_api_key');
        $this->cryptomus['cryptomus_uuid'] = Config::obtain('cryptomus_uuid');
        $this->cryptomus['cryptomus_subtract'] = Config::obtain('cryptomus_subtract');
        $this->cryptomus['cryptomus_lifetime'] = Config::obtain('cryptomus_lifetime');
        $this->cryptomus['cryptomus_currency'] = Config::obtain('cryptomus_currency');
    }

    public static function _name(): string
    {
        return 'cryptomus';
    }

    public static function _enable(): bool
    {
        return self::getActiveGateway('cryptomus');
    }

    public static function _readableName(): string
    {
        return 'Cryptomus';
    }

    public function purchase(ServerRequest $request, Response $response, array $args): ResponseInterface
    {
        $invoiceId = $this->antiXss->xss_clean($request->getParam('invoice_id'));
        $redir = $this->antiXss->xss_clean($request->getParam('redir'));
        $invoice = (new Invoice())->find($invoiceId);

        if ($invoice === null) {
            return $response->withJson([
                'ret' => 0,
                'msg' => 'Invoice not found',
            ]);
        }

        $user = Auth::getUser();

        if ((int) $invoice->user_id !== (int) $user->id) {
            return $response->withJson([
                'ret' => 0,
                'msg' => '无权操作此账单',
            ]);
        }

        $price = $invoice->price;

        if ($price <= 0) {
            return $response->withJson([
                'ret' => 0,
                'msg' => 'Số tiền không hợp lệ',
            ]);
        }

        $pl = (new Paylist())->where('invoice_id', $invoiceId)->first();

        if ($pl === null) {
            $pl = new Paylist();
            $pl->userid = $user->id;
            $pl->invoice_id = $invoiceId;
            $pl->tradeno = self::generateGuid();
        }

        $pl->total = $price;
        $pl->gateway = self::_readableName();
        $pl->save();

        $paymentData = [
            'amount' => $price,
            'currency' => $this->cryptomus['cryptomus_currency'] ?? 'CNY',
            'order_id' => 'sspanel_' . $invoiceId,
            'url_return' => $redir,
            'url_callback' => self::getCallbackUrl(),
            'lifetime' => $this->cryptomus['cryptomus_lifetime'] ?? '3600',
            'subtract' => $this->cryptomus['cryptomus_subtract'] ?? '0',
            'plugin_name' => 'sspanel:2024.1',
            'additional_data' => json_encode(['tradeno' => $pl->tradeno]),
        ];

        $paymentInstance = $this->getPayment();

        try {
            $payment = $paymentInstance->create($paymentData);
        } catch (\Exception $exception) {
            return $response->withJson([
                'ret' => 0,
                'msg' => 'Yêu cầu thanh toán thất bại: ' . $exception->getMessage(),
            ]);
        }

        return $response->withHeader('HX-Redirect', $payment['url'])->withJson([
            'ret' => 1,
            'msg' => 'Đơn hàng đã được tạo, đang chuyển đến trang thanh toán...',
        ]);
    }

    public function notify($request, $response, $args): ResponseInterface
    {
        $payload = trim(file_get_contents('php://input'));
        $data = json_decode($payload, true);
        $additionalData = json_decode($data['additional_data'], true);

        if (! $this->hashEqual($data)) {
            return $response->withJson(['state' => 'fail', 'msg' => 'Sign is not valid']);
        }

        $success = isset($data['is_final']) && $data['is_final'] && ($data['status'] === 'paid' || $data['status'] === 'paid_over' || $data['status'] === 'wrong_amount');
        if ($success) {
            $this->postPayment($additionalData['tradeno']);

            return $response->withJson([
                'ret' => 1,
                'msg' => 'Thanh toán thành công',
            ]);
        }

        return $response->withJson(['state' => 'fail', 'msg' => 'Payment failed']);
    }

    /**
     * @throws Exception
     */
    public static function getPurchaseHTML(): string
    {
        return View::getSmarty()->fetch('gateway/cryptomus.tpl');
    }

    private function getPayment(): CryptomusPayment
    {
        $merchantUuid = trim($this->cryptomus['cryptomus_uuid']);
        $paymentKey = trim($this->cryptomus['cryptomus_api_key']);

        if (! $merchantUuid || ! $paymentKey) {
            throw new Exception('Please fill UUID and API key');
        }

        return new CryptomusPayment($paymentKey, $merchantUuid);
    }

    private function hashEqual($data): bool
    {
        $paymentKey = trim($this->cryptomus['cryptomus_api_key']);

        if (! $paymentKey) {
            return false;
        }

        $signature = $data['sign'] ?? '';
        if (! $signature) {
            return false;
        }

        unset($data['sign']);

        $hash = md5(base64_encode(json_encode($data, JSON_UNESCAPED_UNICODE)) . $paymentKey);
        if (! hash_equals($hash, $signature)) {
            return false;
        }

        return true;
    }
}
