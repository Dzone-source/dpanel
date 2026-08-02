<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\Config;
use App\Models\InviteCode;
use App\Models\LoginIp;
use App\Models\User;
use App\Services\Auth;
use App\Services\Cache;
use App\Services\Captcha;
use App\Services\Filter;
use App\Services\Mail;
use App\Services\MFA\FIDO;
use App\Services\MFA\TOTP;
use App\Services\MFA\WebAuthn;
use App\Services\RateLimit;
use App\Services\Reward;
use App\Utils\Cookie;
use App\Utils\Hash;
use App\Utils\ResponseHelper;
use App\Utils\Tools;
use Exception;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Message\ResponseInterface;
use Ramsey\Uuid\Uuid;
use RedisException;
use Slim\Http\Response;
use Slim\Http\ServerRequest;
use Throwable;
use function array_rand;
use function date;
use function explode;
use function strlen;
use function strtolower;
use function time;
use function trim;

final class AuthController extends BaseController
{
    /**
     * @throws Exception
     */
    public function login(ServerRequest $request, Response $response, array $args): ResponseInterface
    {
        $captcha = [];

        if (Config::obtain('enable_login_captcha')) {
            $captcha = Captcha::generate();
        }

        return $response->write($this->view()
            ->assign('base_url', $_ENV['baseUrl'])
            ->assign('captcha', $captcha)
            ->fetch('auth/login.tpl'));
    }

    public function loginHandle(ServerRequest $request, Response $response, array $args): ResponseInterface
    {
        try {
            if (Config::obtain('enable_login_captcha') && ! Captcha::verify($request->getParams())) {
                return $response->withJson([
                    'ret' => 0,
                    'msg' => 'Hệ thống không thể chấp nhận kết quả xác minh của bạn, vui lòng làm mới trang và thử lại.',
                ]);
            }

            $password = (string) ($request->getParam('password') ?? '');
            $rememberMe = $request->getParam('remember_me') === 'true' ? 1 : 0;
            $email = strtolower(trim($this->antiXss->xss_clean((string) ($request->getParam('email') ?? ''))));
            $redirRaw = $this->antiXss->xss_clean(Cookie::get('redir'));
            $redir = (is_string($redirRaw) && $redirRaw !== '') ? $redirRaw : '/user';
            $user = (new User())->where('email', $email)->first();
            $loginIp = new LoginIp();
            $clientIp = (string) ($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0');

            if ($user === null) {
                try {
                    $loginIp->collectLoginIP($clientIp, 1);
                } catch (Throwable) {
                    // ignore logging errors
                }

                return $response->withJson([
                    'ret' => 0,
                    'msg' => 'Email hoặc mật khẩu không đúng',
                ]);
            }

            if ($password === '' || ! Hash::checkPassword($user->pass, $password)) {
                try {
                    $loginIp->collectLoginIP($clientIp, 1, $user->id);
                } catch (Throwable) {
                    // ignore logging errors
                }

                return $response->withJson([
                    'ret' => 0,
                    'msg' => 'Email hoặc mật khẩu không đúng',
                ]);
            }

            try {
                $mfaStatus = $user->checkMfaStatus();
            } catch (Throwable) {
                $mfaStatus = ['require' => false];
            }

            if (! empty($mfaStatus['require'])) {
                $redis = (new Cache())->initRedis();
                $redis->setex('mfa_login_' . session_id(), 300, json_encode([
                    'userid' => $user->id,
                    'method' => $mfaStatus,
                    'redir' => $redir,
                    'remember_me' => $rememberMe,
                ]));

                return $response
                    ->withHeader('HX-Redirect', '/auth/mfa')
                    ->withJson([
                        'ret' => 1,
                        'msg' => 'Vui lòng hoàn tất xác thực hai bước',
                        'redir' => '/auth/mfa',
                    ]);
            }

            $time = self::loginCookieLifetime((bool) $rememberMe);

            Auth::login($user->id, $time);

            try {
                $loginIp->collectLoginIP($clientIp, 0, $user->id);
            } catch (Throwable) {
                // ignore logging errors
            }

            $user->last_login_time = time();
            $user->save();

            return $response
                ->withHeader('HX-Redirect', $redir)
                ->withJson([
                    'ret' => 1,
                    'msg' => 'Đăng nhập thành công',
                    'redir' => $redir,
                ]);
        } catch (Throwable $e) {
            return $response->withJson([
                'ret' => 0,
                'msg' => 'Lỗi đăng nhập: ' . $e->getMessage(),
            ]);
        }
    }

    public function mfaPage(ServerRequest $request, Response $response, $next): ResponseInterface
    {
        $redis = (new Cache())->initRedis();
        $mfa_session = $redis->get('mfa_login_' . session_id());
        if ($mfa_session === false) {
            return $response->withStatus(302)->withHeader('Location', '/auth/login');
        }
        $mfa_session = json_decode($mfa_session, true);
        return $response->write(
            $this->view()
                ->assign('base_url', $_ENV['baseUrl'])
                ->assign('method', $mfa_session['method'])
                ->fetch('auth/mfa.tpl')
        );
    }

    /**
     * @throws Exception
     */
    public function register(ServerRequest $request, Response $response, $next): ResponseInterface
    {
        $captcha = [];

        if (Config::obtain('enable_reg_captcha')) {
            $captcha = Captcha::generate();
        }

        $invite_code = $this->antiXss->xss_clean($request->getParam('code'));

        return $response->write(
            $this->view()
                ->assign('invite_code', $invite_code)
                ->assign('base_url', $_ENV['baseUrl'])
                ->assign('captcha', $captcha)
                ->fetch('auth/register.tpl')
        );
    }

    /**
     * @throws RedisException
     */
    public function sendVerify(ServerRequest $request, Response $response, $next): ResponseInterface
    {
        if (Config::obtain('reg_email_verify')) {
            $email = strtolower(trim($this->antiXss->xss_clean($request->getParam('email'))));

            if ($email === '') {
                return ResponseHelper::error($response, 'Chưa nhập email');
            }

            // check email format
            $email_check = Filter::checkEmailFilter($email);

            if (! $email_check) {
                return ResponseHelper::error($response, 'Email không hợp lệ');
            }

            if (! (new RateLimit())->checkRateLimit('email_request_ip', $request->getServerParam('REMOTE_ADDR')) ||
                ! (new RateLimit())->checkRateLimit('email_request_address', $email)
            ) {
                return ResponseHelper::error($response, 'Yêu cầu của bạn quá thường xuyên, vui lòng thử lại sau');
            }

            $user = (new User())->where('email', $email)->first();

            if ($user !== null) {
                return ResponseHelper::error($response, 'Email này đã được đăng ký');
            }

            $email_code = Tools::genRandomChar(6);
            $redis = (new Cache())->initRedis();
            $redis->setex('email_verify:' . $email_code, Config::obtain('email_verify_code_ttl'), $email);

            try {
                Mail::send(
                    $email,
                    $_ENV['appName'] . '- Email xác minh',
                    'verify_code.tpl',
                    [
                        'code' => $email_code,
                        'expire' => date('Y-m-d H:i:s', time() + Config::obtain('email_verify_code_ttl')),
                    ]
                );
            } catch (Throwable $e) {
                return ResponseHelper::error(
                    $response,
                    'Gửi email thất bại: ' . $e->getMessage()
                );
            }

            return ResponseHelper::success($response, 'Mã xác minh đã được gửi, vui lòng kiểm tra email.');
        }

        return ResponseHelper::error($response, 'Trang web chưa bật xác minh email');
    }

    /**
     * @throws Exception
     */
    public function registerHelper(
        Response $response,
        $name,
        $email,
        $password,
        $invite_code,
        $imtype,
        $imvalue,
        $money,
        $is_admin_reg
    ): ResponseInterface {
        $redirRaw = $this->antiXss->xss_clean(Cookie::get('redir'));
        $redir = (is_string($redirRaw) && $redirRaw !== '') ? $redirRaw : '/user';
        $configs = Config::getClass('reg');
        // do reg user
        $user = new User();

        $user->user_name = $name;
        $user->email = $email;
        $user->remark = '';
        $user->pass = Hash::passwordHash($password);
        $user->passwd = Tools::genRandomChar(16);
        $user->uuid = Uuid::uuid4()->toString();
        $user->api_token = Tools::genRandomChar(32);
        $user->port = Tools::getSsPort();
        $user->u = 0;
        $user->d = 0;
        $user->method = $configs['reg_method'];
        $user->im_type = $imtype;
        $user->im_value = $imvalue;
        $user->transfer_enable = Tools::gbToB($configs['reg_traffic']);
        $user->auto_reset_day = Config::obtain('free_user_reset_day');
        $user->auto_reset_bandwidth = Config::obtain('free_user_reset_bandwidth');
        $user->daily_mail_enable = $configs['reg_daily_report'];
        $user->is_banned = 0;
        $user->is_shadow_banned = 0;
        $user->is_inactive = 0;

        if ($money > 0) {
            $user->money = $money;
        } else {
            $user->money = 0;
        }

        $user->ref_by = 0;

        if ($invite_code !== '') {
            $invite = (new InviteCode())->where('code', $invite_code)->first();

            if ($invite !== null) {
                $user->ref_by = $invite->user_id;
            }
        }

        $user->class = $configs['reg_class'];
        $user->class_expire = date('Y-m-d H:i:s', time() + (int) $configs['reg_class_time'] * 86400);
        $user->node_iplimit = $configs['reg_ip_limit'];
        $user->node_speedlimit = $configs['reg_speed_limit'];
        $user->reg_date = date('Y-m-d H:i:s');
        $user->reg_ip = $_SERVER['REMOTE_ADDR'];
        $user->theme = $_ENV['theme'];
        $user->locale = $_ENV['locale'];
        $random_group = Config::obtain('random_group');

        if ($random_group === '') {
            $user->node_group = 0;
        } else {
            $user->node_group = $random_group[array_rand(explode(',', $random_group))];
        }

        $user->last_login_time = time();

        if ($user->save() && ! $is_admin_reg) {
            if ($user->ref_by !== 0) {
                Reward::issueRegReward($user->id, $user->ref_by);
            }

            Auth::login($user->id, self::loginCookieLifetime(false));
            (new LoginIp())->collectLoginIP($_SERVER['REMOTE_ADDR'], 0, $user->id);

            return $response
                ->withHeader('HX-Redirect', $redir)
                ->withJson([
                    'ret' => 1,
                    'msg' => 'Đăng ký thành công',
                    'redir' => $redir,
                ]);
        }

        if ($user->id > 0 && $is_admin_reg) {
            return ResponseHelper::success($response, 'Tạo tài khoản thành công');
        }

        return ResponseHelper::error($response, 'Lỗi không xác định');
    }

    /**
     * @throws RedisException
     * @throws Exception
     */
    public function registerHandle(ServerRequest $request, Response $response, array $args): ResponseInterface
    {
        if (Config::obtain('reg_mode') === 'close') {
            return ResponseHelper::error($response, 'Chưa mở đăng ký.');
        }

        if (Config::obtain('enable_reg_captcha') && ! Captcha::verify($request->getParams())) {
            return ResponseHelper::error($response, 'Hệ thống không thể chấp nhận kết quả xác minh của bạn, vui lòng làm mới trang và thử lại.');
        }

        $tos = $request->getParam('tos') === 'true' ? 1 : 0;
        $email = strtolower(trim($this->antiXss->xss_clean($request->getParam('email'))));
        $name = $this->antiXss->xss_clean($request->getParam('name'));
        $password = $request->getParam('password');
        $confirm_password = $request->getParam('confirm_password');
        $invite_code = $this->antiXss->xss_clean(trim($request->getParam('invite_code')));

        if (! $tos) {
            return ResponseHelper::error($response, 'Vui lòng đồng ý với Điều khoản dịch vụ và Chính sách bảo mật');
        }

        if ($name === null || trim((string) $name) === '') {
            return ResponseHelper::error($response, 'Vui lòng nhập biệt danh');
        }

        if ($password === null || $password === '') {
            return ResponseHelper::error($response, 'Vui lòng nhập mật khẩu');
        }

        if (strlen((string) $password) < 8) {
            return ResponseHelper::error($response, 'Mật khẩu phải có ít nhất 8 ký tự');
        }

        if ((string) $password !== (string) $confirm_password) {
            return ResponseHelper::error($response, 'Hai lần nhập mật khẩu không khớp');
        }

        if ($invite_code === '' && Config::obtain('reg_mode') === 'invite') {
            return ResponseHelper::error($response, 'Mã mời không được để trống');
        }

        if ($invite_code !== '') {
            $invite = (new InviteCode())->where('code', $invite_code)->first();

            if ($invite === null) {
                return ResponseHelper::error($response, 'Mã mời không hợp lệ');
            }

            $ref_user = (new User())->where('id', $invite->user_id)->first();

            if ($ref_user === null) {
                return ResponseHelper::error($response, 'Mã mời không hợp lệ');
            }
        }

        $imtype = 0;
        $imvalue = '';

        // check email format
        $email_check = Filter::checkEmailFilter($email);

        if (! $email_check) {
            return ResponseHelper::error($response, 'Email không hợp lệ');
        }
        // check email
        $user = (new User())->where('email', $email)->first();

        if ($user !== null) {
            return ResponseHelper::error($response, 'Email không hợp lệ');
        }

        if (Config::obtain('reg_email_verify')) {
            $redis = (new Cache())->initRedis();
            $email_verify_code = trim($this->antiXss->xss_clean($request->getParam('emailcode')));
            $email_verify = $redis->get('email_verify:' . $email_verify_code);

            if (! $email_verify) {
                return ResponseHelper::error($response, 'Mã xác minh email của bạn không đúng');
            }

            $redis->del('email_verify:' . $email_verify_code);
        }

        return $this->registerHelper($response, $name, $email, $password, $invite_code, $imtype, $imvalue, 0, 0);
    }

    public function logout(ServerRequest $request, Response $response, $next): Response
    {
        Auth::logout();

        return $response->withStatus(302)->withHeader('Location', '/auth/login');
    }

    public function webauthnRequest(ServerRequest $request, Response $response, $next): ResponseInterface
    {
        return $response->withJson(WebAuthn::assertRequest());
    }

    public function webauthnHandle(ServerRequest $request, Response $response, $next): ResponseInterface
    {
        $data = $this->antiXss->xss_clean((array) $request->getParsedBody());
        $redirRaw = $this->antiXss->xss_clean(Cookie::get('redir'));
        $redir = (is_string($redirRaw) && $redirRaw !== '') ? $redirRaw : '/user';
        $result = WebAuthn::assertHandle($data);
        if ($result['ret'] === 1) {
            $user = $result['user'];
            if ($user === null) {
                return $response->withJson([
                    'ret' => 0,
                    'msg' => 'Người dùng không tồn tại',
                ]);
            }
            $rememberMe = $request->getParam('remember_me') === 'true';
            $time = self::loginCookieLifetime($rememberMe);
            Auth::login($user->id, $time);
            $loginIp = new LoginIp();
            $loginIp->collectLoginIP($_SERVER['REMOTE_ADDR'], 0, $user->id);
            $user->last_login_time = time();
            $user->save();
            return $response->withJson([
                'ret' => 1,
                'msg' => 'Đăng nhập thành công',
                'redir' => $redir,
            ]);
        }
        return $response->withJson($result);
    }

    public function totpHandle(ServerRequest $request, Response $response, $next): ResponseInterface
    {
        $redis = (new Cache())->initRedis();
        $login_session = $redis->get('mfa_login_' . session_id());
        if ($login_session === false) {
            return $response->withJson(['ret' => 0, 'msg' => 'Phiên đăng nhập đã hết hạn'])->withHeader('HX-Redirect', '/auth/login');
        }
        $login_session = json_decode($login_session, true);
        $code = $this->antiXss->xss_clean($request->getParam('code'));
        $user = (new User())->where('id', $login_session['userid'])->first();
        if ($user === null) {
            return $response->withJson(['ret' => 0, 'msg' => 'Người dùng không tồn tại'])->withHeader('HX-Redirect', '/auth/login');
        }
        $result = TOTP::assertHandle($user, $code);
        if ($result['ret'] === 1) {
            $redis->del('mfa_login_' . session_id());
            $rememberMe = $login_session['remember_me'];
            $time = self::loginCookieLifetime((bool) $rememberMe);
            Auth::login($user->id, $time);
            $loginIp = new LoginIp();
            $loginIp->collectLoginIP($_SERVER['REMOTE_ADDR'], 0, $user->id);
            $user->last_login_time = time();
            $user->save();
            return $response
                ->withHeader('HX-Redirect', $login_session['redir'])
                ->withJson(['ret' => 1, 'msg' => 'Đăng nhập thành công']);
        }
        return $response->withJson($result);
    }

    public function fidoRequest(ServerRequest $request, Response $response, $next): ResponseInterface
    {
        $redis = (new Cache())->initRedis();
        $login_session = $redis->get('mfa_login_' . session_id());
        if ($login_session === false) {
            return $response->withJson(['ret' => 0, 'msg' => 'Phiên đăng nhập đã hết hạn'])->withHeader('HX-Redirect', '/auth/login');
        }
        $login_session = json_decode($login_session, true);
        $user = (new User())->where('id', $login_session['userid'])->first();
        if ($user === null) {
            return $response->withJson(['ret' => 0, 'msg' => 'Người dùng không tồn tại'])->withHeader('HX-Redirect', '/auth/login');
        }
        return $response->withJson(FIDO::assertRequest($user));
    }

    public function fidoHandle(ServerRequest $request, Response $response, $next): ResponseInterface
    {
        $redis = (new Cache())->initRedis();
        $login_session = $redis->get('mfa_login_' . session_id());
        if ($login_session === false) {
            return $response->withJson(['ret' => 0, 'msg' => 'Phiên đăng nhập đã hết hạn'])->withHeader('HX-Redirect', '/auth/login');
        }
        $login_session = json_decode($login_session, true);
        $data = $this->antiXss->xss_clean((array) $request->getParsedBody());
        $user = (new User())->where('id', $login_session['userid'])->first();
        if ($user === null) {
            return $response->withJson(['ret' => 0, 'msg' => 'Người dùng không tồn tại'])->withHeader('HX-Redirect', '/auth/login');
        }
        $result = FIDO::assertHandle($user, $data);
        if ($result['ret'] === 1) {
            $redis->del('mfa_login_' . session_id());
            $rememberMe = $login_session['remember_me'];
            $time = self::loginCookieLifetime((bool) $rememberMe);
            Auth::login($user->id, $time);
            $loginIp = new LoginIp();
            $loginIp->collectLoginIP($_SERVER['REMOTE_ADDR'], 0, $user->id);
            $user->last_login_time = time();
            $user->save();
            return $response->withJson(['ret' => 1, 'msg' => 'Đăng nhập thành công', 'redir' => $login_session['redir']]);
        }
        return $response->withJson($result);
    }

    /**
     * Cookie login lifetime in seconds.
     * Default session: 7 days. Remember-me: 30 days (configurable).
     */
    private static function loginCookieLifetime(bool $rememberMe): int
    {
        $days = $rememberMe
            ? (int) ($_ENV['rememberMeDuration'] ?? 30)
            : (int) ($_ENV['sessionDuration'] ?? 7);

        if ($days < 1) {
            $days = 1;
        }

        return 86400 * $days;
    }
}
