<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\Ann;
use App\Models\Config;
use App\Services\Analytics;
use App\Services\Auth;
use App\Services\Captcha;
use App\Services\Config\ClientConfig;
use App\Services\Reward;
use App\Services\Subscribe;
use App\Utils\ResponseHelper;
use App\Utils\Tools;
use Exception;
use Psr\Http\Message\ResponseInterface;
use Slim\Http\Response;
use Slim\Http\ServerRequest;
use function json_encode;
use function strtotime;
use function time;

final class UserController extends BaseController
{
    /**
     * @throws Exception
     */
    public function index(ServerRequest $request, Response $response, array $args): ResponseInterface
    {
        $captcha = [];
        $traffic_logs = [];
        $class_expire_days = $this->user->class > 0 ?
            round((strtotime($this->user->class_expire) - time()) / 86400) : 0;
        $ann = (new Ann())->where('status', '>', 0)
            ->orderBy('status', 'desc')
            ->orderBy('sort')
            ->orderBy('date', 'desc')->first();

        if (Config::obtain('enable_checkin') &&
            Config::obtain('enable_checkin_captcha') &&
            $this->user->isAbleToCheckin()) {
            $captcha = Captcha::generate();
        }

        if (Config::obtain('traffic_log')) {
            $hourly_usage = Analytics::getUserTodayHourlyUsage($this->user->id);

            foreach ($hourly_usage as $hour => $usage) {
                $traffic_logs[] = Tools::bToMB((int) $usage);
            }
        }

        $universalSub = Subscribe::getUniversalSubLink($this->user);
        $r2Enabled = filter_var($_ENV['enable_r2_client_download'] ?? 'false', FILTER_VALIDATE_BOOLEAN);
        $clientData = ClientConfig::getClients(
            $universalSub,
            $_ENV['appName'] ?? 'SSPanel',
            $r2Enabled
        );

        // Bandwidth-only packages may leave class at 0 while transfer_enable > 0.
        $has_active_plan = $this->user->class > 0 || $this->user->transfer_enable > 0;
        $class_expire_ts = strtotime((string) $this->user->class_expire);
        $expire_still_valid = $class_expire_ts !== false && $class_expire_ts > time();

        if ($this->user->class > 0) {
            $class_value = 'LV. ' . $this->user->class
                . ($class_expire_days > 0 ? ' · còn ' . $class_expire_days . ' ngày' : '');
        } elseif ($this->user->transfer_enable > 0) {
            $class_value = $this->user->enableTraffic() . ' · đang dùng';
            if ($expire_still_valid) {
                $class_value = $this->user->enableTraffic()
                    . ' · còn ' . (int) round(($class_expire_ts - time()) / 86400) . ' ngày';
            }
        } else {
            $class_value = 'Chưa kích hoạt';
        }

        $info_cards = [
            [
                'title' => 'Gói dịch vụ',
                'value' => $class_value,
                'icon' => 'ti-crown',
                'gradient' => 'gopass-gradient-1',
                'action_url' => '/user/product',
                'cta' => ! $has_active_plan,
                'cta_label' => 'Mua hàng',
                'buy_new' => $has_active_plan,
                'buy_new_label' => 'Mua gói mới',
            ],
            [
                'title' => 'Số dư ví',
                'value' => $this->user->money . ' VND',
                'icon' => 'ti-wallet',
                'gradient' => 'gopass-gradient-2',
                'action_url' => '/user/money',
            ],
            [
                'title' => 'Thiết bị đồng thời',
                'value' => $this->user->node_iplimit > 0
                    ? $this->user->node_iplimit . ' thiết bị'
                    : 'Không giới hạn',
                'icon' => 'ti-devices',
                'gradient' => 'gopass-gradient-3',
            ],
            [
                'title' => 'Tốc độ cổng',
                'value' => $this->user->node_speedlimit > 0
                    ? $this->user->node_speedlimit . ' Mbps'
                    : 'Không giới hạn',
                'icon' => 'ti-bolt',
                'gradient' => 'gopass-gradient-4',
            ],
        ];

        return $response->write(
            $this->view()
                ->assign('ann', $ann)
                ->assign('captcha', $captcha)
                ->assign('traffic_logs', json_encode($traffic_logs))
                ->assign('class_expire_days', $class_expire_days)
                ->assign('UniversalSub', $universalSub)
                ->assign('clientData', json_encode($clientData['clients']))
                ->assign('platformIcons', json_encode($clientData['icons']))
                ->assign('user_class', $this->user->class)
                ->assign('user_money', $this->user->money)
                ->assign('ip_limit', $this->user->node_iplimit)
                ->assign('speed_limit', $this->user->node_speedlimit)
                ->assign('info_cards', $info_cards)
                ->fetch('user/index.tpl')
        );
    }

    /**
     * @throws Exception
     */
    public function announcement(ServerRequest $request, Response $response, array $args): ResponseInterface
    {
        $anns = (new Ann())->where('status', '>', 0)
            ->orderBy('status', 'desc')
            ->orderBy('sort')
            ->orderBy('date', 'desc')->get();

        return $response->write(
            $this->view()
                ->assign('anns', $anns)
                ->fetch('user/announcement.tpl')
        );
    }

    public function checkin(ServerRequest $request, Response $response, array $args): ResponseInterface
    {
        if (! Config::obtain('enable_checkin') || ! $this->user->isAbleToCheckin()) {
            return ResponseHelper::error($response, 'Chưa thể điểm danh');
        }

        if (Config::obtain('enable_checkin_captcha')) {
            $ret = Captcha::verify($request->getParams());

            if (! $ret) {
                return ResponseHelper::error($response, 'Hệ thống không thể chấp nhận kết quả xác minh của bạn, vui lòng làm mới trang và thử lại');
            }
        }

        $traffic = Reward::issueCheckinReward($this->user->id);

        if (! $traffic) {
            return ResponseHelper::error($response, 'Điểm danh thất bại');
        }

        return $response->withJson([
            'ret' => 1,
            'msg' => 'Đã nhận được ' . $traffic . 'MB MB lưu lượng',
            'data' => [
                'last-checkin-time' => Tools::toDateTime(time()),
            ],
        ]);
    }

    /**
     * @throws Exception
     */
    public function banned(ServerRequest $request, Response $response, array $args): ResponseInterface
    {
        return $response->write(
            $this->view()
                ->assign('banned_reason', $this->user->banned_reason)
                ->fetch('user/banned.tpl')
        );
    }

    public function logout(ServerRequest $request, Response $response, array $args): Response
    {
        Auth::logout();

        return $response->withStatus(302)->withHeader('Location', '/');
    }
}
