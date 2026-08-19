<?php

declare(strict_types=1);

namespace App\Services\Gateway;

use App\Models\Config;
use App\Models\Invoice;
use App\Models\Paylist;
use App\Services\Auth;
use App\Services\View;
// use Exception;
use Psr\Http\Message\ResponseInterface;
use Slim\Http\Response;
use Slim\Http\ServerRequest;

final class Smogate extends Base
{
    public static function _name(): string
    {
        return 'smogate';
    }

    public static function _enable(): bool
    {
        return self::getActiveGateway('smogate');
    }

    public static function _readableName(): string
    {
        return 'Nạp tiền qua Alipay trực tuyến';
    }

    public function post($data)
    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, 'https://' . Config::obtain('smogate_app_id') . '.vless.org/v1/gateway/pay');
        curl_setopt($curl, CURLOPT_HEADER, 0);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        curl_setopt($curl, CURLOPT_HTTPHEADER, ['User-Agent: Smogate ' . Config::obtain('smogate_app_id')]);
        $data = curl_exec($curl);
        curl_close($curl);

        return $data;
    }

    public function prepareSign($data)
    {
        ksort($data);
        return http_build_query($data);
    }

    public function sign($data)
    {
        return strtolower(md5($data . Config::obtain('smogate_app_secret')));
    }

    public function verify($data, $signature)
    {
        unset($data['sign']);
        $mySign = $this->sign($this->prepareSign($data));
        return $mySign === $signature;
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

        $user = Auth::getUser();

        if ((int) $invoice->user_id !== (int) $user->id) {
            return $response->withJson([
                'ret' => 0,
                'msg' => '无权操作此账单',
            ]);
        }

        $amount = $invoice->price;

        if ($amount <= 0) {
            return $response->withJson([
                'ret' => 0,
                'msg' => 'Số tiền đơn hàng không đúng',
            ]);
        }

        $pl = (new Paylist())->where('invoice_id', $invoice_id)->first();

        if ($pl === null) {
            $pl = new Paylist();
            $pl->userid = $user->id;
            $pl->invoice_id = $invoice_id;
            $pl->tradeno = self::generateGuid();
        }

        $pl->total = $amount;
        $pl->gateway = self::_readableName();
        $pl->save();

        $data = [
            'method' => 'alipay',
            'app_id' => Config::obtain('smogate_app_id'),
            'out_trade_no' => $pl->tradeno,
            'total_amount' => (int) ($pl->total * 100),
            'notify_url' => self::getCallbackUrl(),
        ];
        $params = $this->prepareSign($data);
        $data['sign'] = $this->sign($params);
        $result = json_decode($this->post($data), true);

        if (isset($result['errors'])) {
            return $response->withJson([
                'ret' => 0,
                'msg' => $result['errors'][array_keys($result['errors'])[0]],
            ]);
        }
        if (isset($result['message'])) {
            return $response->withJson([
                'ret' => 0,
                'msg' => $result['message'],
            ]);
        }

        return $response->withJson([
            'ret' => 1,
            'type' => $this->isMobile() ? 'url' : 'qrcode',
            'qrcode' => $result['data'],
            'amount' => $pl->total,
            'pid' => $pl->tradeno,
        ]);
    }

    public function notify($request, $response, $args): ResponseInterface
    {
        if (! $this->verify($request->getParams(), $request->getParam('sign'))) {
            die('FAIL');
        }
        $this->postPayment($request->getParam('out_trade_no'), 'smogate');
        die('SUCCESS');
    }

    public static function getPurchaseHTML(): string
    {
        return View::getSmarty()->fetch('gateway/smogate.tpl');
    }

    private function isMobile()
    {
        return strpos(strtolower($_SERVER['HTTP_USER_AGENT']), 'mobile') !== false;
    }
}
