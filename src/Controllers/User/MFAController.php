<?php

declare(strict_types=1);

namespace App\Controllers\User;

use App\Controllers\BaseController;
use App\Models\MFADevice;
use App\Services\MFA\FIDO;
use App\Services\MFA\TOTP;
use App\Services\MFA\WebAuthn;
use Exception;
use Psr\Http\Message\ResponseInterface;
use Slim\Http\Response;
use Slim\Http\ServerRequest;

/**
 *  MFAController
 */
final class MFAController extends BaseController
{
    public function webauthnRegisterRequest(ServerRequest $request, Response $response, array $args): ResponseInterface
    {
        return $response->withJson(WebAuthn::registerRequest($this->user));
    }

    public function webauthnRegisterHandle(ServerRequest $request, Response $response, array $args): ResponseInterface
    {
        try {
            return $response->withJson(WebAuthn::registerHandle($this->user, $this->antiXss->xss_clean($request)));
        } catch (Exception $e) {
            return $response->withJson(['ret' => 0, 'msg' => 'Yêu cầu thất bại: ' . $e->getMessage()]);
        }
    }

    public function webauthnDelete(ServerRequest $request, Response $response, array $args): ResponseInterface
    {
        $webauthnDevice = (new MFADevice())
            ->where('id', (int) $args['id'])
            ->where('userid', $this->user->id)
            ->where('type', 'passkey')
            ->first();
        if ($webauthnDevice === null) {
            return $response->withJson([
                'ret' => 0,
                'msg' => 'Thiết bị không tồn tại',
            ]);
        }
        $webauthnDevice->delete();
        return $response->withHeader('HX-Refresh', 'true')->withJson([
            'ret' => 1,
            'msg' => 'Xóa thành công',
        ]);
    }

    public function totpRegisterRequest(ServerRequest $request, Response $response, array $args): ResponseInterface
    {
        return $response->withJson(TOTP::registerRequest($this->user));
    }

    public function totpRegisterHandle(ServerRequest $request, Response $response, array $args): ResponseInterface
    {
        try {
            return $response->withJson(TOTP::registerHandle($this->user, $this->antiXss->xss_clean($request->getParam('code', ''))));
        } catch (Exception $e) {
            return $response->withJson(['ret' => 0, 'msg' => 'Yêu cầu thất bại: ' . $e->getMessage()]);
        }
    }

    public function totpDelete(ServerRequest $request, Response $response, array $args): ResponseInterface
    {
        $totpDevice = (new MFADevice())
            ->where('userid', $this->user->id)
            ->where('type', 'totp')
            ->first();
        if ($totpDevice === null) {
            return $response->withJson([
                'ret' => 0,
                'msg' => 'Thiết bị không tồn tại',
            ]);
        }
        $totpDevice->delete();
        return $response->withHeader('HX-Refresh', 'true')->withJson([
            'ret' => 1,
            'msg' => 'Xóa thành công',
        ]);
    }

    public function fidoRegisterRequest(ServerRequest $request, Response $response, array $args): ResponseInterface
    {
        return $response->withJson(FIDO::registerRequest($this->user));
    }

    public function fidoRegisterHandle(ServerRequest $request, Response $response, array $args): ResponseInterface
    {
        try {
            return $response->withJson(FIDO::registerHandle($this->user, $this->antiXss->xss_clean($request->getParsedBody())));
        } catch (Exception $e) {
            return $response->withJson(['ret' => 0, 'msg' => 'Yêu cầu thất bại: ' . $e->getMessage()]);
        }
    }

    public function fidoDelete(ServerRequest $request, Response $response, array $args): ResponseInterface
    {
        $fidoDevice = (new MFADevice())
            ->where('id', (int) $args['id'])
            ->where('userid', $this->user->id)
            ->where('type', 'fido')
            ->first();
        if ($fidoDevice === null) {
            return $response->withJson([
                'ret' => 0,
                'msg' => 'Thiết bị không tồn tại',
            ]);
        }
        $fidoDevice->delete();
        return $response->withHeader('HX-Refresh', 'true')->withJson([
            'ret' => 1,
            'msg' => 'Xóa thành công',
        ]);
    }
}
