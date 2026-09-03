<?php

declare(strict_types=1);

namespace App\Controllers\User;

use App\Controllers\BaseController;
use App\Models\OnlineLog;
use App\Services\Subscribe;
use App\Services\Subscribe\NodeConfig;
use App\Utils\Tools;
use Exception;
use Psr\Http\Message\ResponseInterface;
use Slim\Http\Response;
use Slim\Http\ServerRequest;
use function time;

final class ServerController extends BaseController
{
    private const ONLINE_WINDOW_SECONDS = 120;

    /**
     * @throws Exception
     */
    public function index(ServerRequest $request, Response $response, array $args): ResponseInterface
    {
        $nodes = Subscribe::getUserNodes($this->user, true);
        $node_list = [];
        $onlineByNode = $this->countOnlineIpsByNode();

        foreach ($nodes as $node) {
            $nodeId = (int) $node->id;
            $node_list[] = [
                'id' => $node->id,
                'name' => $node->name,
                'class' => (int) $node->node_class,
                'color' => $node->color,
                'connection_type' => $node->connection_type,
                'sort' => $this->displayProtocol($node),
                // Prefer live OnlineLog IP count; fall back to last traffic-push value.
                'online_user' => (int) ($onlineByNode[$nodeId] ?? $node->online_user ?? 0),
                'online' => $node->getNodeOnlineStatus(),
                'traffic_rate' => $node->traffic_rate,
                'is_dynamic_rate' => $node->is_dynamic_rate,
                'node_bandwidth' => Tools::autoBytes($node->node_bandwidth),
                'node_bandwidth_limit' => $node->node_bandwidth_limit === 0 ? 'Không giới hạn' :
                    Tools::autoBytes($node->node_bandwidth_limit),
            ];
        }

        return $response->write(
            $this->view()
                ->assign('servers', $node_list)
                ->fetch('user/server.tpl')
        );
    }

    /**
     * Distinct client IPs reported by XrayR /aliveip within the recent window, keyed by node_id.
     *
     * @return array<int, int>
     */
    private function countOnlineIpsByNode(): array
    {
        $cutoff = time() - self::ONLINE_WINDOW_SECONDS;

        $rows = (new OnlineLog())
            ->newQuery()
            ->where('last_time', '>', $cutoff)
            ->selectRaw('node_id, COUNT(DISTINCT ip) AS cnt')
            ->groupBy('node_id')
            ->get();

        $map = [];
        foreach ($rows as $row) {
            $map[(int) $row->node_id] = (int) $row->cnt;
        }

        return $map;
    }

    private function displayProtocol(object $node): string
    {
        $label = $node->sort();
        if ((int) $node->sort === 11) {
            $cfg = NodeConfig::decode($node->custom_config ?? null);
            if (NodeConfig::isVless($cfg) || NodeConfig::isReality($cfg)) {
                return 'VLESS';
            }
        }

        return $label;
    }
}
