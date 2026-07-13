<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\AuthController;
use App\Controllers\BaseController;
use App\Models\Config;
use App\Models\User;
use App\Models\UserMoneyLog;
use App\Utils\Hash;
use App\Utils\Tools;
use Exception;
use Psr\Http\Message\ResponseInterface;
use Slim\Http\Response;
use Slim\Http\ServerRequest;

final class UserController extends BaseController
{
    private static array $details = [
        'field' => [
            'op' => 'Thao tác',
            'id' => 'ID người dùng',
            'user_name' => 'Biệt danh',
            'email' => 'Email',
            'money' => 'Số dư',
            'ref_by' => 'Người mời',
            'transfer_enable' => 'Giới hạn lưu lượng',
            'transfer_used' => 'Lưu lượng kỳ hiện tại',
            'class' => 'Cấp',
            'is_admin' => 'Là quản trị viên',
            'is_banned' => 'Bị cấm',
            'is_inactive' => 'Không hoạt động',
            'reg_date' => 'Thời gian đăng ký',
            'class_expire' => 'Hết hạn cấp',
        ],
        'create_dialog' => [
            [
                'id' => 'email',
                'info' => 'Email đăng nhập',
                'type' => 'input',
                'placeholder' => '',
            ],
            [
                'id' => 'password',
                'info' => 'Mật khẩu đăng nhập',
                'type' => 'input',
                'placeholder' => 'Để trống sẽ tạo ngẫu nhiên',
            ],
            [
                'id' => 'ref_by',
                'info' => 'Người mời',
                'type' => 'input',
                'placeholder' => 'ID người dùng người mời, có thể để trống',
            ],
            [
                'id' => 'balance',
                'info' => 'Số dư tài khoản',
                'type' => 'input',
                'placeholder' => '-1 là theo cài đặt mặc định, giá trị khác là chỉ định',
            ],
        ],
    ];

    private static array $update_field = [
        'email',
        'user_name',
        'pass',
        'money',
        'ref_by',
        'port',
        'method',
        'transfer_enable',
        'node_group',
        'class',
        'class_expire',
        'auto_reset_day',
        'auto_reset_bandwidth',
        'node_speedlimit',
        'node_iplimit',
        'banned_reason',
        'remark',
    ];

    /**
     * @throws Exception
     */
    public function index(ServerRequest $request, Response $response, array $args): ResponseInterface
    {
        return $response->write(
            $this->view()
                ->assign('details', self::$details)
                ->fetch('admin/user/index.tpl')
        );
    }

    /**
     * @throws Exception
     */
    public function create(ServerRequest $request, Response $response, array $args): ResponseInterface
    {
        $email = $request->getParam('email');
        $ref_by = $request->getParam('ref_by');
        $password = $request->getParam('password');
        $balance = $request->getParam('balance');

        if ($email === '') {
            return $response->withJson([
                'ret' => 0,
                'msg' => 'Email không được để trống',
            ]);
        }

        $exist = (new User())->where('email', $email)->first();

        if ($exist !== null) {
            return $response->withJson([
                'ret' => 0,
                'msg' => 'Email đã tồn tại',
            ]);
        }

        if ($password === '') {
            $password = Tools::genRandomChar(16);
        }

        (new AuthController())->registerHelper(
            $response,
            'user',
            $email,
            $password,
            '',
            0,
            '',
            $balance,
            1
        );
        $user = (new User())->where('email', $email)->first();

        if ($ref_by !== '') {
            $user->ref_by = (int) $ref_by;
            $user->save();
        }

        return $response->withJson([
            'ret' => 1,
            'msg' => 'Thêm thành công, email người dùng: ' . $email . ' Mật khẩu: '.$password,
        ]);
    }

    /**
     * @throws Exception
     */
    public function edit(ServerRequest $request, Response $response, array $args): ResponseInterface
    {
        $user = (new User())->find($args['id']);
        $user->last_use_time = Tools::toDateTime($user->last_use_time);
        $user->last_check_in_time = Tools::toDateTime($user->last_check_in_time);
        $user->last_login_time = Tools::toDateTime($user->last_login_time);

        return $response->write(
            $this->view()
                ->assign('update_field', self::$update_field)
                ->assign('edit_user', $user)
                ->assign('ss_methods', Tools::getSsMethod())
                ->fetch('admin/user/edit.tpl')
        );
    }

    public function update(ServerRequest $request, Response $response, array $args): ResponseInterface
    {
        $id = (int) $args['id'];
        $user = (new User())->find($id);

        if ($request->getParam('pass') !== '' && $request->getParam('pass') !== null) {
            $user->pass = Hash::passwordHash($request->getParam('pass'));

            if (Config::obtain('enable_forced_replacement')) {
                $user->removeLink();
            }
        }

        if ($request->getParam('money') !== '' &&
            $request->getParam('money') !== null &&
            (float) $request->getParam('money') !== $user->money
        ) {
            $money = (float) $request->getParam('money');
            $diff = $money - $user->money;
            $remark = ($diff > 0 ? 'Quản trị viên thêm số dư' : 'Quản trị viên trừ số dư');
            (new UserMoneyLog())->add($id, (float) $user->money, $money, $diff, $remark);
            $user->money = $money;
        }

        $user->email = $request->getParam('email');
        $user->user_name = $request->getParam('user_name');
        $user->ref_by = $request->getParam('ref_by');
        $user->port = $request->getParam('port');
        $user->method = $request->getParam('method');
        $user->transfer_enable = Tools::autoBytesR($request->getParam('transfer_enable'));
        $user->node_group = $request->getParam('node_group');
        $user->class = $request->getParam('class');
        $user->class_expire = $request->getParam('class_expire');
        $user->auto_reset_day = $request->getParam('auto_reset_day');
        $user->auto_reset_bandwidth = $request->getParam('auto_reset_bandwidth');
        $user->node_speedlimit = $request->getParam('node_speedlimit');
        $user->node_iplimit = $request->getParam('node_iplimit');
        $user->locale = 'vi_VN';
        $user->is_admin = $request->getParam('is_admin') === 'true' ? 1 : 0;
        $user->ga_enable = $request->getParam('ga_enable') === 'true' ? 1 : 0;
        $user->is_shadow_banned = $request->getParam('is_shadow_banned') === 'true' ? 1 : 0;
        $user->is_banned = $request->getParam('is_banned') === 'true' ? 1 : 0;
        $user->banned_reason = $request->getParam('banned_reason');
        $user->remark = $request->getParam('remark');

        if (! $user->save()) {
            return $response->withJson([
                'ret' => 0,
                'msg' => 'Cập nhật thất bại',
            ]);
        }

        return $response->withJson([
            'ret' => 1,
            'msg' => 'Cập nhật thành công',
        ]);
    }

    public function delete(ServerRequest $request, Response $response, array $args): ResponseInterface
    {
        $id = $args['id'];
        $user = (new User())->find((int) $id);

        if (! $user->kill()) {
            return $response->withJson([
                'ret' => 0,
                'msg' => 'Xóa thất bại',
            ]);
        }

        return $response->withJson([
            'ret' => 1,
            'msg' => 'Xóa thành công',
        ]);
    }

    public function ajax(ServerRequest $request, Response $response, array $args): ResponseInterface
    {
        $users = (new User())->orderBy('id', 'desc')->get();

        foreach ($users as $user) {
            $user->op = '<button class="btn btn-red" id="delete-user-' . $user->id . '" 
            onclick="deleteUser(' . $user->id . ')">Xóa</button>
            <a class="btn btn-primary" href="/admin/user/' . $user->id . '/edit">Chỉnh sửa</a>';
            $user->transfer_enable = $user->enableTraffic();
            $user->transfer_used = $user->usedTraffic();
            $user->is_admin = $user->is_admin === 1 ? 'Có' : 'Không';
            $user->is_banned = $user->is_banned === 1 ? 'Có' : 'Không';
            $user->is_inactive = $user->is_inactive === 1 ? 'Có' : 'Không';
        }

        return $response->withJson([
            'users' => $users,
        ]);
    }
}
