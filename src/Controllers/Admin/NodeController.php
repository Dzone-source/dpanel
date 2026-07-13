<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\Config;
use App\Models\Node;
use App\Services\I18n;
use App\Services\Notification;
use App\Utils\Tools;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Http\Message\ResponseInterface;
use Slim\Http\Response;
use Slim\Http\ServerRequest;
use Smarty\Exception as SmartyException;
use Telegram\Bot\Exceptions\TelegramSDKException;
use function json_decode;
use function json_encode;
use function round;
use function str_replace;
use function trim;

final class NodeController extends BaseController
{
    private static array $details = [
        'field' => [
            'op' => 'Thao tác',
            'id' => 'ID máy chủ',
            'name' => 'Tên',
            'server' => 'Địa chỉ',
            'type' => 'Trạng thái',
            'sort' => 'Loại',
            'traffic_rate' => 'Hệ số',
            'is_dynamic_rate' => 'Hệ số động',
            'dynamic_rate_type' => 'Phương thức tính hệ số động',
            'node_class' => 'Cấp',
            'node_group' => 'Nhóm',
            'node_bandwidth_limit' => 'Giới hạn lưu lượng/GB',
            'node_bandwidth' => 'Lưu lượng đã dùng/GB',
            'bandwidthlimit_resetday' => 'Ngày đặt lại',
        ],
    ];

    private static array $update_field = [
        'name',
        'server',
        'traffic_rate',
        'is_dynamic_rate',
        'dynamic_rate_type',
        'max_rate',
        'max_rate_time',
        'min_rate',
        'min_rate_time',
        'node_group',
        'node_speedlimit',
        'sort',
        'node_class',
        'node_bandwidth_limit',
        'bandwidthlimit_resetday',
    ];

    /**
     * 后台节点页面
     *
     * @throws SmartyException
     */
    public function index(ServerRequest $request, Response $response, array $args): ResponseInterface
    {
        return $response->write(
            $this->view()
                ->assign('details', self::$details)
                ->fetch('admin/node/index.tpl')
        );
    }

    /**
     * 后台创建节点页面
     *
     * @throws SmartyException
     */
    public function create(ServerRequest $request, Response $response, array $args): ResponseInterface
    {
        return $response->write(
            $this->view()
                ->assign('update_field', self::$update_field)
                ->fetch('admin/node/create.tpl')
        );
    }

    /**
     * 后台添加节点
     */
    public function add(ServerRequest $request, Response $response, array $args): ResponseInterface
    {
        $node = new Node();

        $node->name = $request->getParam('name');
        $node->node_group = $request->getParam('node_group');
        $node->server = trim($request->getParam('server'));
        $node->traffic_rate = $request->getParam('traffic_rate') ?? 1;
        $node->is_dynamic_rate = $request->getParam('is_dynamic_rate') === 'true' ? 1 : 0;
        $node->dynamic_rate_type = $request->getParam('dynamic_rate_type') ?? 0;
        $node->dynamic_rate_config = json_encode([
            'max_rate' => $request->getParam('max_rate') ?? 1,
            'max_rate_time' => $request->getParam('max_rate_time') ?? 22,
            'min_rate' => $request->getParam('min_rate') ?? 1,
            'min_rate_time' => $request->getParam('min_rate_time') ?? 3,
        ]);

        $custom_config = $request->getParam('custom_config') ?? '{}';

        if ($custom_config !== '') {
            $node->custom_config = $custom_config;
        } else {
            $node->custom_config = '{}';
        }

        $node->node_speedlimit = $request->getParam('node_speedlimit');
        $node->type = $request->getParam('type') === 'true' ? 1 : 0;
        $node->sort = $request->getParam('sort');
        $node->node_class = $request->getParam('node_class');
        $node->node_bandwidth_limit = Tools::gbToB($request->getParam('node_bandwidth_limit'));
        $node->bandwidthlimit_resetday = $request->getParam('bandwidthlimit_resetday');
        $node->password = Tools::genRandomChar(32);

        if (! $node->save()) {
            return $response->withJson([
                'ret' => 0,
                'msg' => 'Thêm thất bại',
            ]);
        }

        if (Config::obtain('im_bot_group_notify_add_node')) {
            try {
                Notification::notifyUserGroup(
                    str_replace(
                        '%node_name%',
                        $request->getParam('name'),
                        I18n::trans('bot.node_added', $_ENV['locale'])
                    )
                );
            } catch (TelegramSDKException | GuzzleException) {
                return $response->withJson([
                    'ret' => 1,
                    'msg' => 'Thêm thành công, nhưng thông báo IM Bot thất bại',
                    'node_id' => $node->id,
                ]);
            }
        }

        return $response->withJson([
            'ret' => 1,
            'msg' => 'Thêm thành công',
            'node_id' => $node->id,
        ]);
    }

    /**
     * 后台Chỉnh sửa指定节点页面
     *
     * @throws SmartyException
     */
    public function edit(ServerRequest $request, Response $response, array $args): ResponseInterface
    {
        $node = (new Node())->find($args['id']);

        $dynamic_rate_config = json_decode($node->dynamic_rate_config);
        $node->max_rate = $dynamic_rate_config?->max_rate ?? 1;
        $node->max_rate_time = $dynamic_rate_config?->max_rate_time ?? 22;
        $node->min_rate = $dynamic_rate_config?->min_rate ?? 1;
        $node->min_rate_time = $dynamic_rate_config?->min_rate_time ?? 3;

        $node->node_bandwidth = Tools::autoBytes($node->node_bandwidth);
        $node->node_bandwidth_limit = Tools::bToGB($node->node_bandwidth_limit);

        return $response->write(
            $this->view()
                ->assign('node', $node)
                ->assign('update_field', self::$update_field)
                ->fetch('admin/node/edit.tpl')
        );
    }

    /**
     * 后台更新指定节点内容
     */
    public function update(ServerRequest $request, Response $response, array $args): ResponseInterface
    {
        $node = (new Node())->find($args['id']);

        $node->name = $request->getParam('name');
        $node->node_group = $request->getParam('node_group') ?? 0;
        $node->server = trim($request->getParam('server'));
        $node->traffic_rate = $request->getParam('traffic_rate') ?? 1;
        $node->is_dynamic_rate = $request->getParam('is_dynamic_rate') === 'true' ? 1 : 0;
        $node->dynamic_rate_type = $request->getParam('dynamic_rate_type') ?? 0;
        $node->dynamic_rate_config = json_encode([
            'max_rate' => $request->getParam('max_rate') ?? 1,
            'max_rate_time' => $request->getParam('max_rate_time') ?? 0,
            'min_rate' => $request->getParam('min_rate') ?? 1,
            'min_rate_time' => $request->getParam('min_rate_time') ?? 0,
        ]);

        $custom_config = $request->getParam('custom_config') ?? '{}';

        if ($custom_config !== '') {
            $node->custom_config = $custom_config;
        } else {
            $node->custom_config = '{}';
        }

        $node->node_speedlimit = $request->getParam('node_speedlimit');
        $node->type = $request->getParam('type') === 'true' ? 1 : 0;
        $node->sort = $request->getParam('sort');
        $node->node_class = $request->getParam('node_class');
        $node->node_bandwidth_limit = Tools::gbToB($request->getParam('node_bandwidth_limit'));
        $node->bandwidthlimit_resetday = $request->getParam('bandwidthlimit_resetday');

        if (! $node->save()) {
            return $response->withJson([
                'ret' => 0,
                'msg' => 'Cập nhật thất bại',
            ]);
        }

        if (Config::obtain('im_bot_group_notify_update_node')) {
            try {
                Notification::notifyUserGroup(
                    str_replace(
                        '%node_name%',
                        $request->getParam('name'),
                        I18n::trans('bot.node_updated', $_ENV['locale'])
                    )
                );
            } catch (TelegramSDKException | GuzzleException) {
                return $response->withJson([
                    'ret' => 1,
                    'msg' => 'Cập nhật thành công, nhưng thông báo IM Bot thất bại',
                ]);
            }
        }

        return $response->withJson([
            'ret' => 1,
            'msg' => 'Cập nhật thành công',
        ]);
    }

    public function resetPassword(ServerRequest $request, Response $response, array $args): ResponseInterface
    {
        $node = (new Node())->find($args['id']);
        $node->password = Tools::genRandomChar(32);
        $node->save();

        return $response->withJson([
            'ret' => 1,
            'msg' => 'Đặt lại khóa giao tiếp máy chủ thành công',
        ]);
    }

    public function resetBandwidth(ServerRequest $request, Response $response, array $args): ResponseInterface
    {
        $node = (new Node())->find($args['id']);
        $node->node_bandwidth = 0;
        $node->save();

        return $response->withJson([
            'ret' => 1,
            'msg' => 'Đặt lại lưu lượng máy chủ thành công',
        ]);
    }

    /**
     * 后台Xóa指定节点
     */
    public function delete(ServerRequest $request, Response $response, array $args): ResponseInterface
    {
        $node = (new Node())->find($args['id']);

        if (! $node->delete()) {
            return $response->withJson([
                'ret' => 0,
                'msg' => 'Xóa thất bại',
            ]);
        }

        if (Config::obtain('im_bot_group_notify_delete_node')) {
            try {
                Notification::notifyUserGroup(
                    str_replace(
                        '%node_name%',
                        $node->name,
                        I18n::trans('bot.node_deleted', $_ENV['locale'])
                    )
                );
            } catch (TelegramSDKException | GuzzleException) {
                return $response->withJson([
                    'ret' => 1,
                    'msg' => 'Xóa thành công, nhưng thông báo IM Bot thất bại',
                ]);
            }
        }

        return $response->withJson([
            'ret' => 1,
            'msg' => 'Xóa thành công',
        ]);
    }

    public function copy($request, $response, $args)
    {
        $old_node = (new Node())->find($args['id']);
        $new_node = $old_node->replicate([
            'node_bandwidth',
        ]);
        $new_node->name .= ' (bản sao)';
        $new_node->node_bandwidth = 0;
        $new_node->password = Tools::genRandomChar(32);

        if (! $new_node->save()) {
            return $response->withJson([
                'ret' => 0,
                'msg' => 'Sao chép thất bại',
            ]);
        }

        return $response->withJson([
            'ret' => 1,
            'msg' => 'Sao chép thành công',
        ]);
    }

    /**
     * 后台节点页面 AJAX
     */
    public function ajax(ServerRequest $request, Response $response, array $args): ResponseInterface
    {
        $nodes = (new Node())->orderBy('id', 'desc')->get();

        foreach ($nodes as $node) {
            $node->op = '<button class="btn btn-red" id="delete-node-' . $node->id . '" 
            onclick="deleteNode(' . $node->id . ')">Xóa</button>
            <button class="btn btn-orange" id="copy-node-' . $node->id . '" 
            onclick="copyNode(' . $node->id . ')">Sao chép</button>
            <a class="btn btn-primary" href="/admin/node/' . $node->id . '/edit">Chỉnh sửa</a>';
            $node->type = $node->type();
            $node->sort = $node->sort();
            $node->is_dynamic_rate = $node->isDynamicRate();
            $node->dynamic_rate_type = $node->dynamicRateType();
            $node->node_bandwidth = round(Tools::bToGB($node->node_bandwidth), 2);
            $node->node_bandwidth_limit = Tools::bToGB($node->node_bandwidth_limit);
        }

        return $response->withJson([
            'nodes' => $nodes,
        ]);
    }
}
