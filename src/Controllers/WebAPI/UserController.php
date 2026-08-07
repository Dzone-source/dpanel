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

        // Soft-offline: keep serving users but apply a floor speed limit.
        // Returning [] made XrayR delete every Trojan account → "not a valid user".
        $nodeOverBandwidth = $node->node_bandwidth_limit !== 0
            && $node->node_bandwidth_limit <= $node->node_bandwidth;

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

        // Keep uuid + passwd for Trojan so XrayR can accept either password.
        // V2 family (sort 11) only needs uuid; SS keeps passwd.
        $keys_unset = match ($node->sort) {
            14 => ['u', 'd', 'transfer_enable', 'method', 'port'],
            12, 13, 15, 11 => ['u', 'd', 'transfer_enable', 'method', 'port', 'passwd'],
            2 => ['u', 'd', 'transfer_enable', 'method', 'port'],
            1 => ['u', 'd', 'transfer_enable', 'method', 'port', 'uuid'],
            default => ['u', 'd', 'transfer_enable', 'uuid']
        };

        // Do not send live alive_ip counts to XrayR: ParseUserListResponse removes users when
        // alive_ip >= DeviceLimit (panel limit OR XrayR config DeviceLimit override) → trojan
        // "not a valid user". Panel still tracks IPs via /aliveip for the user dashboard.
        $users = [];

        // XrayR shared rate-limit buckets historically made Hiddify upload
        // speedtests look "disconnected". Default: do not send Mbps caps to XrayR.
        $disableXrayrSpeedLimit = (bool) ($_ENV['disable_xrayr_speed_limit'] ?? true);
        // Floor when keep_connect / node bandwidth soft-throttle is active.
        // 5 Mbps is too low — apps abort mid upload. Prefer 100+.
        $keepConnectFloor = (float) ($_ENV['keep_connect_speedlimit'] ?? 100);

        foreach ($users_raw as $user_raw) {
            if ($user_raw->transfer_enable <= $user_raw->u + $user_raw->d) {
                // Hard-removing exhausted users causes client timeouts. Prefer keep_connect
                // throttle; if keep_connect is off, still omit (policy), but default is on.
                if (! ($_ENV['keep_connect'] ?? true)) {
                    continue;
                }
                if (! $disableXrayrSpeedLimit) {
                    $user_raw->node_speedlimit = max($keepConnectFloor, (float) $user_raw->node_speedlimit);
                    if ($user_raw->node_speedlimit <= 0) {
                        $user_raw->node_speedlimit = $keepConnectFloor;
                    }
                }
            }

            if ($nodeOverBandwidth && ! $disableXrayrSpeedLimit) {
                $user_raw->node_speedlimit = max($keepConnectFloor, (float) $user_raw->node_speedlimit);
                if ($user_raw->node_speedlimit <= 0) {
                    $user_raw->node_speedlimit = $keepConnectFloor;
                }
            }

            // Cap by node-level Mbps when speed limits are enabled for XrayR.
            if (! $disableXrayrSpeedLimit) {
                $nodeLimit = (float) $node->node_speedlimit;
                $userLimit = (float) $user_raw->node_speedlimit;
                if ($nodeLimit > 0) {
                    $user_raw->node_speedlimit = $userLimit > 0 ? min($userLimit, $nodeLimit) : $nodeLimit;
                }
            }

            $ip_limit = (int) $user_raw->node_iplimit;

            // Do NOT hard-remove users from this list when over IP limit.

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

            // Temporarily disable IP online limit for XrayR (node_iplimit=0 = unlimited).
            // Set $_ENV['disable_ip_online_limit'] = false to restore panel ip_limit.
            $disable_ip_limit = (bool) ($_ENV['disable_ip_online_limit'] ?? true);
            $user_raw->node_iplimit = $disable_ip_limit ? 0 : $ip_limit;
            $user_raw->alive_ip = self::reportedAliveIpForXrayR();

            if ($disableXrayrSpeedLimit) {
                $user_raw->node_speedlimit = 0;
            }

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
        $activeUserIds = [];

        foreach ($data as $log) {
            $u = $log?->u;
            $d = $log?->d;
            $user_id = $log?->user_id;

            if ($user_id) {
                $billed_u = $u * $rate;
                $billed_d = $d * $rate;

                $user = (new User())->find($user_id);

                if ($user === null) {
                    continue;
                }

                $user->update([
                    'last_use_time' => time(),
                    'u' => $user->u + $billed_u,
                    'd' => $user->d + $billed_d,
                    'transfer_total' => $user->transfer_total + $u + $d,
                    'transfer_today' => $user->transfer_today + $billed_u + $billed_d,
                ]);

                if (((int) $u) + ((int) $d) > 0) {
                    $activeUserIds[(int) $user_id] = true;
                }
            }

            if ($is_traffic_log) {
                (new HourlyUsage())->add((int) $user_id, (int) ($u + $d));
            }

            $sum += $u + $d;
        }

        $node->update([
            'node_bandwidth' => $node->node_bandwidth + $sum,
            // Unique users with traffic in this push (legacy used count($data)-1 and was often wrong).
            'online_user' => count($activeUserIds),
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

        // Refresh cached online_user from distinct IPs in the recent window.
        $onlineIps = (int) (new OnlineLog())
            ->newQuery()
            ->where('node_id', $node_id)
            ->where('last_time', '>', time() - 120)
            ->selectRaw('COUNT(DISTINCT ip) AS c')
            ->value('c');
        $node->update(['online_user' => $onlineIps]);

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

    /**
     * XrayR drops users from its trojan list when alive_ip >= DeviceLimit (see
     * api/sspanel ParseUserListResponse). Any non-zero value risks "not a valid user"
     * when XrayR config DeviceLimit differs from panel node_iplimit or after NAT rebind.
     */
    private static function reportedAliveIpForXrayR(): int
    {
        return 0;
    }
}
