<?php

declare(strict_types=1);

namespace App\Controllers\WebAPI;

use App\Controllers\BaseController;
use App\Models\Config;
use App\Models\DetectLog;
use App\Models\HourlyUsage;
use App\Models\Node;
use App\Models\OnlineLog;
use App\Models\User;
use App\Services\DynamicRate;
use App\Utils\ResponseHelper;
use App\Utils\Tools;
use Psr\Http\Message\ResponseInterface;
use Slim\Http\Response;
use Slim\Http\ServerRequest;
use function count;
use function date;
use function is_array;
use function json_decode;
use function time;

final class UserController extends BaseController
{
    /**
     * GET /mod_mu/users
     */
    public function index(ServerRequest $request, Response $response, array $args): ResponseInterface
    {
        $node_id = $request->getQueryParam('node_id');
        $node = (new Node())->find($node_id);

        if ($node === null) {
            return ResponseHelper::error($response, 'Node not found.');
        }

        if ($node->type === 0) {
            return ResponseHelper::error($response, 'Node is not enabled.');
        }

        $node->update(['node_heartbeat' => time()]);

        // Soft-offline: empty success list instead of error — XrayR treats API errors as
        // auth failures and may drop all sessions on the node.
        if ($node->node_bandwidth_limit !== 0 && $node->node_bandwidth_limit <= $node->node_bandwidth) {
            return ResponseHelper::successWithDataEtag($request, $response, []);
        }

        $users_raw = (new User())->where(
            'is_banned',
            0
        )->where(
            'class_expire',
            '>',
            date('Y-m-d H:i:s')
        )->where(
            static function ($query) use ($node): void {
                $query->where('class', '>=', $node->node_class)
                    ->where(static function ($query) use ($node): void {
                        if ($node->node_group !== 0) {
                            $query->where('node_group', $node->node_group);
                        }
                    });
            }
        )->orWhere(
            'is_admin',
            1
        )->get([
            'id',
            'u',
            'd',
            'transfer_enable',
            'node_speedlimit',
            'node_iplimit',
            'method',
            'port',
            'passwd',
            'uuid',
        ]);

        // Keep node_iplimit + alive_ip so XrayR can enforce DeviceLimit softly.
        // Do not hard-remove over-limit users here — that causes client timeouts when
        // OnlineLog still holds a previous IP after Wi‑Fi/cellular or NAT rebind.
        $keys_unset = match ($node->sort) {
            14, 11 => ['u', 'd', 'transfer_enable', 'method', 'port', 'passwd'],
            2 => ['u', 'd', 'transfer_enable', 'method', 'port'],
            1 => ['u', 'd', 'transfer_enable', 'method', 'port', 'uuid'],
            default => ['u', 'd', 'transfer_enable', 'uuid']
        };

        // Batch online IP counts (active within last 90 seconds) for multi-node coordination.
        $alive_ip_counts = [];
        if ($users_raw->isNotEmpty()) {
            $alive_ip_counts = (new OnlineLog())
                ->whereIn('user_id', $users_raw->pluck('id'))
                ->where('last_time', '>', time() - 90)
                ->groupBy('user_id')
                ->selectRaw('user_id, COUNT(*) AS cnt')
                ->pluck('cnt', 'user_id')
                ->all();
        }

        $users = [];

        foreach ($users_raw as $user_raw) {
            if ($user_raw->transfer_enable <= $user_raw->u + $user_raw->d) {
                // Hard-removing exhausted users causes client timeouts. Prefer keep_connect
                // throttle; if keep_connect is off, still omit (policy), but default is on.
                if (! ($_ENV['keep_connect'] ?? true)) {
                    continue;
                }
                // Soft throttle — 1 Mbps is often so low apps look "disconnected".
                $floor = (float) ($_ENV['keep_connect_speedlimit'] ?? 5);
                $user_raw->node_speedlimit = max($floor, (float) $user_raw->node_speedlimit);
                if ($user_raw->node_speedlimit <= 0) {
                    $user_raw->node_speedlimit = $floor;
                }
            }

            $ip_limit = (int) $user_raw->node_iplimit;
            $alive_ip = (int) ($alive_ip_counts[$user_raw->id] ?? 0);

            // Do NOT hard-remove users from this list when over IP limit.
            // Stale OnlineLog rows (Wi‑Fi↔cellular, NAT rebind) within the 90s window
            // would make the whole account vanish from XrayR → client "timeout".
            // XrayR enforces DeviceLimit using node_iplimit + alive_ip below.

            if ($node->sort === 1) {
                $method = json_decode($node->custom_config)->method ?? '2022-blake3-aes-128-gcm';
                $user_pk = Tools::genSs2022UserPk($user_raw->passwd, $method);

                if (! $user_pk) {
                    continue;
                }

                $user_raw->passwd = $user_pk;
            }

            foreach ($keys_unset as $key) {
                unset($user_raw->$key);
            }

            // Expose limit + current online IP count for XrayR DeviceLimit / AliveIP.
            $user_raw->node_iplimit = $ip_limit;
            $user_raw->alive_ip = $alive_ip;

            $users[] = $user_raw;
        }

        return ResponseHelper::successWithDataEtag($request, $response, $users);
    }

    /**
     * POST /mod_mu/users/traffic
     */
    public function addTraffic(ServerRequest $request, Response $response, array $args): ResponseInterface
    {
        $data = json_decode($request->getBody()->__toString());

        if (! $data || ! is_array($data->data)) {
            return ResponseHelper::error($response, 'Invalid data.');
        }

        $data = $data->data;
        $node_id = $request->getQueryParam('node_id');
        $node = (new Node())->find($node_id);

        if ($node === null) {
            return ResponseHelper::error($response, 'Node not found.');
        }

        if ($node->type === 0) {
            return ResponseHelper::error($response, 'Node is not enabled.');
        }

        $rate = 1;

        if ($node->is_dynamic_rate) {
            $dynamic_rate_config = json_decode($node->dynamic_rate_config);

            $dynamic_rate_type = match ($node->dynamic_rate_type) {
                1 => 'linear',
                default => 'logistic',
            };

            $rate = DynamicRate::getRateByTime(
                (float) $dynamic_rate_config?->max_rate,
                (int) $dynamic_rate_config?->max_rate_time,
                (float) $dynamic_rate_config?->min_rate,
                (int) $dynamic_rate_config?->min_rate_time,
                (int) date('H'),
                $dynamic_rate_type
            );
        } else {
            $rate = $node->traffic_rate;
        }

        $sum = 0;
        $is_traffic_log = Config::obtain('traffic_log');

        foreach ($data as $log) {
            $u = $log?->u;
            $d = $log?->d;
            $user_id = $log?->user_id;

            if ($user_id) {
                $billed_u = $u * $rate;
                $billed_d = $d * $rate;

                $user = (new User())->find($user_id);

                $user->update([
                    'last_use_time' => time(),
                    'u' => $user->u + $billed_u,
                    'd' => $user->d + $billed_d,
                    'transfer_total' => $user->transfer_total + $u + $d,
                    'transfer_today' => $user->transfer_today + $billed_u + $billed_d,
                ]);
            }

            if ($is_traffic_log) {
                (new HourlyUsage())->add((int) $user_id, (int) ($u + $d));
            }

            $sum += $u + $d;
        }

        $node->update([
            'node_bandwidth' => $node->node_bandwidth + $sum,
            'online_user' => count($data) - 1,
        ]);

        return ResponseHelper::success($response, 'ok');
    }

    /**
     * POST /mod_mu/users/aliveip
     */
    public function addAliveIp(ServerRequest $request, Response $response, array $args): ResponseInterface
    {
        $data = json_decode($request->getBody()->__toString());

        if (! $data || ! is_array($data->data)) {
            return ResponseHelper::error($response, 'Invalid data.');
        }

        $data = $data->data;
        $node_id = $request->getQueryParam('node_id');
        $node = (new Node())->find($node_id);

        if ($node === null) {
            return ResponseHelper::error($response, 'Node not found.');
        }

        if ($node->type === 0) {
            return ResponseHelper::error($response, 'Node is not enabled.');
        }

        foreach ($data as $log) {
            $ip = (string) $log?->ip;
            $user_id = (int) $log?->user_id;

            if (Tools::isIPv4($ip)) {
                // convert IPv4 Address to IPv4-mapped IPv6 Address
                $ip = '::ffff:' . $ip;
            } elseif (! Tools::isIPv6($ip)) {
                // either IPv4 or IPv6 Address
                continue;
            }

            (new OnlineLog())->upsert(
                [
                    'user_id' => $user_id,
                    'ip' => $ip,
                    'node_id' => $node_id,
                    'first_time' => time(),
                    'last_time' => time(),
                ],
                ['user_id', 'ip'],
                ['node_id', 'last_time']
            );
        }

        return ResponseHelper::success($response, 'ok');
    }

    /**
     * POST /mod_mu/users/detectlog
     */
    public function addDetectLog(ServerRequest $request, Response $response, array $args): ResponseInterface
    {
        $data = json_decode($request->getBody()->__toString());

        if (! $data || ! is_array($data->data)) {
            return ResponseHelper::error($response, 'Invalid data.');
        }

        $data = $data->data;
        $node_id = $request->getQueryParam('node_id');
        $node = (new Node())->find($node_id);

        if ($node === null) {
            return ResponseHelper::error($response, 'Node not found.');
        }

        if ($node->type === 0) {
            return ResponseHelper::error($response, 'Node is not enabled.');
        }

        foreach ($data as $log) {
            $list_id = (int) $log?->list_id;
            $user_id = (int) $log?->user_id;

            (new DetectLog())->insert([
                'user_id' => $user_id,
                'list_id' => $list_id,
                'node_id' => $node_id,
                'datetime' => time(),
            ]);
        }

        return ResponseHelper::success($response, 'ok');
    }
}
