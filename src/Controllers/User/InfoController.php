<?php

declare(strict_types=1);

namespace App\Controllers\User;

use App\Controllers\BaseController;
use App\Models\Config;
use App\Models\MFADevice;
use App\Models\User;
use App\Services\Auth;
use App\Services\Cache;
use App\Services\Filter;
use App\Utils\Hash;
use App\Utils\ResponseHelper;
use App\Utils\Tools;
use Exception;
use Psr\Http\Message\ResponseInterface;
use Ramsey\Uuid\Uuid;
use RedisException;
use Slim\Http\Response;
use Slim\Http\ServerRequest;
use function in_array;
use function strlen;
use function strtolower;
use const BASE_PATH;

final class InfoController extends BaseController
{
    /**
     * @throws Exception
     */
    public function index(ServerRequest $request, Response $response, array $args): ResponseInterface
    {
        $themes = Tools::getDir(BASE_PATH . '/resources/views');
        $methods = Tools::getSsMethod();
        $webauthnDevices = array_map(static fn ($item) => (object) $item, (new MFADevice())->where('userid', $this->user->id)->where('type', 'passkey')->get()->toArray());
        $totpDevices = array_map(static fn ($item) => (object) $item, (new MFADevice())->where('userid', $this->user->id)->where('type', 'totp')->get()->toArray());
        $fidoDevices = array_map(static fn ($item) => (object) $item, (new MFADevice())->where('userid', $this->user->id)->where('type', 'fido')->get()->toArray());

        return $response->write(
            $this->view()
                ->assign('user', $this->user)
                ->assign('themes', $themes)
                ->assign('methods', $methods)
                ->assign('webauthnDevices', $webauthnDevices)
                ->assign('totpDevices', $totpDevices)
                ->assign('fidoDevices', $fidoDevices)
                ->fetch('user/edit.tpl')
        );
    }

    /**
     * @throws RedisException
     */
    public function updateEmail(ServerRequest $request, Response $response, array $args): ResponseInterface
    {
        $new_email = $this->antiXss->xss_clean($request->getParam('newemail'));
        $user = $this->user;
        $old_email = $user->email;

        if (! $_ENV['enable_change_email'] || $user->is_shadow_banned) {
            return ResponseHelper::error($response, 'Cập nhật thất bại');
        }

        if ($new_email === '') {
            return ResponseHelper::error($response, 'Chưa nhập email');
        }

        if (! Filter::checkEmailFilter($new_email)) {
            return ResponseHelper::error($response, 'Email không hợp lệ');
        }

        if ($new_email === $old_email) {
            return ResponseHelper::error($response, 'Email mới không thể giống email cũ');
        }

        if ((new User())->where('email', $new_email)->first() !== null) {
            return ResponseHelper::error($response, 'Email đã được sử dụng');
        }

        if (Config::obtain('reg_email_verify')) {
            $redis = (new Cache())->initRedis();
            $email_verify_code = $request->getParam('emailcode');
            $email_verify = $redis->get('email_verify:' . $email_verify_code);

            if (! $email_verify) {
                return ResponseHelper::error($response, 'Mã xác minh email của bạn không đúng');
            }

            $redis->del('email_verify:' . $email_verify_code);
        }

        $user->email = $new_email;

        if (! $user->save()) {
            return ResponseHelper::error($response, 'Cập nhật thất bại');
        }

        return $response->withJson([
            'ret' => 1,
            'msg' => 'Cập nhật thành công',
            'data' => [
                'email' => $user->email,
            ],
        ]);
    }

    public function updateUsername(ServerRequest $request, Response $response, array $args): ResponseInterface
    {
        $newusername = $this->antiXss->xss_clean($request->getParam('newusername'));
        $user = $this->user;

        if ($user->is_shadow_banned) {
            return ResponseHelper::error($response, 'Cập nhật thất bại');
        }

        $user->user_name = $newusername;

        if (! $user->save()) {
            return ResponseHelper::error($response, 'Cập nhật thất bại');
        }

        return $response->withJson([
            'ret' => 1,
            'msg' => 'Cập nhật thành công',
            'data' => [
                'username' => $user->user_name,
            ],
        ]);
    }

    public function unbindIm(ServerRequest $request, Response $response, array $args): ResponseInterface
    {
        if (! $this->user->unbindIM()) {
            return ResponseHelper::error($response, 'Hủy liên kết thất bại');
        }

        return $response->withHeader('HX-Refresh', 'true');
    }

    public function updatePassword(ServerRequest $request, Response $response, array $args): ResponseInterface
    {
        $password = $request->getParam('password');
        $new_password = $request->getParam('new_password');
        $confirm_new_password = $request->getParam('confirm_new_password');
        $user = $this->user;

        if ($password === '' || $new_password === '' || $confirm_new_password === '') {
            return ResponseHelper::error($response, 'Mật khẩu không được để trống');
        }

        if (! Hash::checkPassword($user->pass, $password)) {
            return ResponseHelper::error($response, 'Mật khẩu cũ không đúng');
        }

        if ($new_password !== $confirm_new_password) {
            return ResponseHelper::error($response, 'Hai lần nhập không khớp');
        }

        if (strlen($new_password) < 8) {
            return ResponseHelper::error($response, 'Mật khẩu quá ngắn');
        }

        $user->pass = Hash::passwordHash($new_password);

        if (! $user->save()) {
            return ResponseHelper::error($response, 'Cập nhật thất bại');
        }

        if (Config::obtain('enable_forced_replacement')) {
            $user->removeLink();
        }

        return ResponseHelper::success($response, 'Cập nhật thành công');
    }

    public function resetPasswd(ServerRequest $request, Response $response, array $args): ResponseInterface
    {
        $user = $this->user;
        $user->passwd = Tools::genRandomChar(16);
        $user->uuid = Uuid::uuid4();

        if (! $user->save()) {
            return ResponseHelper::error($response, 'Đặt lại thất bại');
        }

        return $response->withJson([
            'ret' => 1,
            'msg' => 'Đặt lại thành công',
            'data' => [
                'passwd' => $user->passwd,
                'uuid' => $user->uuid,
            ],
        ]);
    }

    public function resetApiToken(ServerRequest $request, Response $response, array $args): ResponseInterface
    {
        $user = $this->user;
        $user->api_token = Tools::genRandomChar(32);

        if (! $user->save()) {
            return ResponseHelper::error($response, 'Đặt lại thất bại');
        }

        return ResponseHelper::success($response, 'Đặt lại thành công');
    }

    public function updateMethod(ServerRequest $request, Response $response, array $args): ResponseInterface
    {
        $user = $this->user;
        $method = strtolower($this->antiXss->xss_clean($request->getParam('method')));

        if ($method === '') {
            ResponseHelper::error($response, 'Dữ liệu nhập không hợp lệ');
        }

        if (! Tools::isParamValidate('method', $method)) {
            ResponseHelper::error($response, 'Mã hóa không hợp lệ');
        }

        $user->method = $method;

        if (! $user->save()) {
            return ResponseHelper::error($response, 'Cập nhật thất bại');
        }

        return ResponseHelper::success($response, 'Cập nhật thành công');
    }

    public function resetUrl(ServerRequest $request, Response $response, array $args): ResponseInterface
    {
        $this->user->removeLink();

        return ResponseHelper::success($response, 'Đặt lại thành công');
    }

    public function updateDailyMail(ServerRequest $request, Response $response, array $args): ResponseInterface
    {
        $value = (int) $request->getParam('mail');

        if (! in_array($value, [0, 1, 2])) {
            return ResponseHelper::error($response, 'Tham số không hợp lệ');
        }

        $user = $this->user;
        $user->daily_mail_enable = $value;

        if (! $user->save()) {
            return ResponseHelper::error($response, 'Cập nhật thất bại');
        }

        return ResponseHelper::success($response, 'Cập nhật thành công');
    }

    public function updateContactMethod(ServerRequest $request, Response $response, array $args): ResponseInterface
    {
        $value = (int) $request->getParam('contact');

        if (! in_array($value, [1, 2])) {
            return ResponseHelper::error($response, 'Tham số không hợp lệ');
        }

        $user = $this->user;
        $user->contact_method = $value;

        if (! $user->save()) {
            return ResponseHelper::error($response, 'Cập nhật thất bại');
        }

        return ResponseHelper::success($response, 'Cập nhật thành công');
    }

    public function updateTheme(ServerRequest $request, Response $response, array $args): ResponseInterface
    {
        $theme = $this->antiXss->xss_clean($request->getParam('theme'));
        $user = $this->user;

        if ($theme === '') {
            return ResponseHelper::error($response, 'Chủ đề không được để trống');
        }

        $user->theme = $theme;

        if (! $user->save()) {
            return ResponseHelper::error($response, 'Cập nhật thất bại');
        }

        return $response->withHeader('HX-Refresh', 'true');
    }

    public function updateThemeMode(ServerRequest $request, Response $response, array $args): ResponseInterface
    {
        $theme_mode = (int) $this->antiXss->xss_clean($request->getParam('theme_mode'));
        $user = $this->user;

        $user->is_dark_mode = in_array($theme_mode, [0, 1, 2]) ? $theme_mode : 0;

        if (! $user->save()) {
            return ResponseHelper::error($response, 'Chuyển đổi thất bại');
        }

        return $response->withHeader('HX-Refresh', 'true');
    }

    public function sendToGulag(ServerRequest $request, Response $response, array $args): ResponseInterface
    {
        $user = $this->user;
        $password = $request->getParam('password');

        if ($password === '' || ! Hash::checkPassword($user->pass, $password)) {
            return ResponseHelper::error($response, 'Mật khẩu không đúng');
        }

        if ($_ENV['enable_kill']) {
            Auth::logout();
            $user->kill();

            return $response->withHeader('HX-Redirect', '/auth/login');
        }

        return ResponseHelper::error($response, 'Tự xóa tài khoản chưa được bật');
    }
}
