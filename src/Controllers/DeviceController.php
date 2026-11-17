<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\User;
use App\Models\UserDevice;
use App\Services\DeviceProfiles;
use App\Utils\ResponseHelper;
use DomainException;
use Psr\Http\Message\ResponseInterface;
use Slim\Http\Response;
use Slim\Http\ServerRequest;

final class DeviceController extends BaseController
{
    private function csrf(): string
    {
        return DeviceProfiles::csrf($this->user);
    }

    public function index(ServerRequest $request, Response $response, array $args): ResponseInterface
    {
        $devices = UserDevice::where('user_id', $this->user->id)->orderByDesc('id')->get();
        $active = [];
        $cutoff = time() - 180;
        foreach ($devices as $device) {
            $presence = \App\Services\DB::table('user_device_presence')->where('device_id', $device->id)
                ->where('last_seen_at', '>', $cutoff)->get();
            $active[] = [
                'id' => $device->id, 'name' => $device->name,
                'revoked' => $device->revoked_at !== null,
                'online' => $device->revoked_at === null && $presence->isNotEmpty(),
                'ips' => implode(', ', $presence->pluck('ip')->unique()->all()),
                'connections' => (int) $presence->sum('connections'),
                'last_seen' => $device->last_seen_at ? date('d/m/Y H:i:s', $device->last_seen_at) : 'Chưa ghi nhận',
                'url' => $device->revoked_at === null ? $device->subscriptionUrl() . '/clash' : '',
            ];
        }
        $messages = [
            'enabled' => 'Đã bật profile thiết bị. Profile chung cũ sẽ ngừng được cấp quyền khi node đồng bộ.',
            'created' => 'Đã tạo profile. Chờ node đồng bộ trước khi kết nối.',
            'revoked' => 'Đã thu hồi profile. Node sẽ cắt phiên khi đồng bộ quyền.',
        ];
        $notice = $request->getQueryParam('notice', '');
        $notice = is_string($notice) ? $notice : '';
        return $response->withHeader('Cache-Control', 'no-store')->write($this->view()
            ->assign('devices', $active)->assign('csrf', $this->csrf())
            ->assign('device_mode', (bool) $this->user->device_profiles_enabled)
            ->assign('device_limit', (int) $this->user->device_limit)
            ->assign('notice', $messages[$notice] ?? '')
            ->fetch('user/devices.tpl'));
    }

    public function change(ServerRequest $request, Response $response, array $args): ResponseInterface
    {
        if (! hash_equals($this->csrf(), (string) $request->getParam('csrf', ''))) {
            return ResponseHelper::error($response, 'Phiên xác thực không hợp lệ.', 403);
        }
        $action = $request->getParam('action');
        try {
            if ($action === 'enable') {
                User::where('id', $this->user->id)->update(['device_profiles_enabled' => 1]);
                $notice = 'enabled';
            } elseif ($action === 'create') {
                DeviceProfiles::create((int) $this->user->id, (string) $request->getParam('name', ''));
                $notice = 'created';
            } elseif ($action === 'revoke') {
                if (! DeviceProfiles::revoke((int) $this->user->id, (int) $request->getParam('device_id'))) {
                    return ResponseHelper::error($response, 'Không tìm thấy profile đang hoạt động.', 404);
                }
                $notice = 'revoked';
            } else {
                return ResponseHelper::error($response, 'Thao tác không hợp lệ.', 400);
            }
        } catch (DomainException $error) {
            return ResponseHelper::error($response, $error->getMessage(), 422);
        }
        return $response->withStatus(303)->withHeader('Cache-Control', 'no-store')
            ->withHeader('Location', '/user/devices?notice=' . $notice);
    }
    public function adminRevoke(ServerRequest $request, Response $response, array $args): ResponseInterface
    {
        if (! $this->user->is_admin || ! hash_equals($this->csrf(), (string) $request->getParam('csrf', ''))) {
            return ResponseHelper::error($response, 'Không có quyền thu hồi profile.', 403);
        }
        if (! DeviceProfiles::revoke((int) $args['id'], (int) $args['device_id'])) {
            return ResponseHelper::error($response, 'Không tìm thấy profile đang hoạt động.', 404);
        }
        return $response->withStatus(303)->withHeader('Cache-Control', 'no-store')
            ->withHeader('Location', '/admin/user/' . (int) $args['id'] . '/edit');
    }
}
