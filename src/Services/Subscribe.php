<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Link;
use App\Models\Node;
use App\Services\Subscribe\Clash;
use App\Services\Subscribe\General;
use App\Services\Subscribe\Hiddify;
use App\Services\Subscribe\Json;
use App\Services\Subscribe\SingBox;
use App\Services\Subscribe\SIP002;
use App\Services\Subscribe\SIP008;
use App\Services\Subscribe\SS;
use App\Services\Subscribe\Trojan;
use App\Services\Subscribe\Uri;
use App\Services\Subscribe\V2Ray;
use App\Services\Subscribe\V2RayJson;
use App\Utils\Tools;
use Illuminate\Support\Collection;

final class Subscribe
{
    public static function getUniversalSubLink($user): string
    {
        $userid = $user->id;
        $token = (new Link())->where('userid', $userid)->first();

        if ($token === null) {
            $token = new Link();
            $token->userid = $userid;
            $token->token = Tools::genSubToken();
            $token->save();
        }

        return $_ENV['subUrl'] . '/sub/' . $token->token;
    }

    public static function getUserNodes($user, bool $show_all_nodes = false): Collection
    {
        $query = Node::query();
        $query->where('type', 1);

        if (! $show_all_nodes) {
            $query->where('node_class', '<=', $user->class);
        }

        if (! $user->is_admin) {
            $group = ($user->node_group !== 0 ? [0, $user->node_group] : [0]);
            $query->whereIn('node_group', $group);
        }

        $nodes = $query->where(static function ($query): void {
            $query->where('node_bandwidth_limit', '=', 0)->orWhereRaw('node_bandwidth < node_bandwidth_limit');
        })->orderBy('node_class')
            ->orderBy('name')
            ->get();

        // Strip legacy "host;port=443|host=sni" server strings so clients get a real hostname.
        return $nodes->each(static function ($node): void {
            $parsed = Tools::parseNodeServer((string) $node->server);
            $node->server = $parsed['server'];

            $cfg = json_decode((string) ($node->custom_config ?? '{}'), true);
            if (! is_array($cfg)) {
                $cfg = [];
            }

            $changed = false;
            if (($cfg['host'] ?? '') === '' && isset($parsed['params']['host'])) {
                $cfg['host'] = $parsed['params']['host'];
                $changed = true;
            }
            if (! isset($cfg['offset_port_node']) && isset($parsed['params']['port']) && $parsed['params']['port'] !== '') {
                $cfg['offset_port_node'] = (int) $parsed['params']['port'];
                $changed = true;
            }

            if ($changed) {
                $node->custom_config = json_encode($cfg, JSON_UNESCAPED_SLASHES);
            }
        });
    }

    public static function getContent($user, string $type): string
    {
        return self::getClient($type)->getContent($user);
    }

    public static function getClient(string $type): Json|SS|SIP002|V2Ray|Trojan|Clash|SIP008|SingBox|V2RayJson|General|Hiddify|Uri
    {
        return match ($type) {
            'ss' => new SS(),
            'sip002' => new SIP002(),
            'v2ray' => new V2Ray(),
            'trojan' => new Trojan(),
            'clash' => new Clash(),
            'sip008' => new SIP008(),
            'singbox' => new SingBox(),
            'v2rayjson' => new V2RayJson(),
            'general' => new General(),
            'hiddify' => new Hiddify(),
            'uri' => new Uri(),
            default => new Json(),
        };
    }
}
