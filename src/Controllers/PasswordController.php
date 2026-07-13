<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\Config;
use App\Models\User;
use App\Services\Cache;
use App\Services\Captcha;
use App\Services\Password;
use App\Services\RateLimit;
use App\Utils\Hash;
use App\Utils\ResponseHelper;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Message\ResponseInterface;
use RedisException;
use Slim\Http\Response;
use Slim\Http\ServerRequest;
use Smarty\Exception;
use function strlen;
use function strtolower;

final class PasswordController extends BaseController
{
    /**
     * @throws Exception
     */
    public function reset(ServerRequest $request, Response $response, array $args): ResponseInterface
    {
        $captcha = [];

        if (Config::obtain('enable_reset_password_captcha')) {
            $captcha = Captcha::generate();
        }

        return $response->write(
            $this->view()
                ->assign('captcha', $captcha)
                ->fetch('password/reset.tpl')
        );
    }

    public function handleReset(ServerRequest $request, Response $response, array $args): ResponseInterface
    {
        if (Config::obtain('enable_reset_password_captcha')) {
            $ret = Captcha::verify($request->getParams());

            if (! $ret) {
                return ResponseHelper::error($response, 'Hệ thống không thể chấp nhận kết quả xác minh của bạn, vui lòng làm mới trang và thử lại');
            }
        }

        $email = strtolower($this->antiXss->xss_clean($request->getParam('email')));

        if ($email === '') {
            return ResponseHelper::error($response, 'Chưa nhập email');
        }

        if (! (new RateLimit())->checkRateLimit('email_request_ip', $request->getServerParam('REMOTE_ADDR')) ||
            ! (new RateLimit())->checkRateLimit('email_request_address', $email)
        ) {
            return ResponseHelper::error($response, 'Yêu cầu của bạn quá thường xuyên, vui lòng thử lại sau');
        }

        $user = (new User())->where('email', $email)->first();
        $msg = 'Nếu tài khoản của bạn tồn tại trong cơ sở dữ liệu của chúng tôi, liên kết đặt lại mật khẩu sẽ được gửi đến email tương ứng';

        if ($user !== null) {
            try {
                Password::sendResetEmail($email);
            } catch (ClientExceptionInterface|RedisException) {
                $msg = 'Gửi email thất bại';
            }
        }

        return ResponseHelper::success($response, $msg);
    }

    /**
     * @throws Exception
     */
    public function token(ServerRequest $request, Response $response, array $args)
    {
        $token = $this->antiXss->xss_clean($args['token']);
        $redis = (new Cache())->initRedis();

        try {
            $email = $redis->get('password_reset:' . $token);
        } catch (RedisException) {
            return $response->withStatus(302)->withHeader('Location', '/password/reset');
        }

        if (! $email) {
            return $response->withStatus(302)->withHeader('Location', '/password/reset');
        }

        return $response->write(
            $this->view()->fetch('password/token.tpl')
        );
    }

    public function handleToken(ServerRequest $request, Response $response, array $args): ResponseInterface
    {
        $token = $this->antiXss->xss_clean($request->getParam('token'));
        $password = $request->getParam('password');
        $confirm_password = $request->getParam('confirm_password');

        if ($password !== $confirm_password) {
            return ResponseHelper::error($response, 'Hai lần nhập không khớp');
        }

        if (strlen($password) < 8) {
            return ResponseHelper::error($response, 'Mật khẩu quá ngắn');
        }

        $redis = (new Cache())->initRedis();

        try {
            $email = $redis->get('password_reset:' . $token);
            $redis->del('password_reset:' . $token);
        } catch (RedisException) {
            return ResponseHelper::error($response, 'Liên kết không hợp lệ');
        }

        if (! $email) {
            return ResponseHelper::error($response, 'Liên kết không hợp lệ');
        }

        $user = (new User())->where('email', $email)->first();

        if ($user === null) {
            return ResponseHelper::error($response, 'Liên kết không hợp lệ');
        }
        // reset password
        $hashPassword = Hash::passwordHash($password);
        $user->pass = $hashPassword;

        if (! $user->save()) {
            return ResponseHelper::error($response, 'Đặt lại thất bại, vui lòng thử lại');
        }

        if (Config::obtain('enable_forced_replacement')) {
            $user->removeLink();
        }

        return ResponseHelper::success($response, 'Đặt lại thành công');
    }
}
