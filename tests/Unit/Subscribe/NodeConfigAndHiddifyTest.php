<?php

declare(strict_types=1);

namespace Tests\Unit\Subscribe;

use App\Services\Subscribe\Clash;
use App\Services\Subscribe\Hiddify;
use App\Services\Subscribe\NodeConfig;
use App\Services\Subscribe\SingBox;
use App\Services\Subscribe\V2Ray;
use PHPUnit\Framework\TestCase;
use stdClass;
use function array_merge;
use function yaml_emit;
use const YAML_UTF8_ENCODING;

final class NodeConfigAndHiddifyTest extends TestCase
{
    public function testTruthyParsing(): void
    {
        $this->assertTrue(NodeConfig::isTruthy(true));
        $this->assertTrue(NodeConfig::isTruthy(1));
        $this->assertTrue(NodeConfig::isTruthy('1'));
        $this->assertTrue(NodeConfig::isTruthy('true'));
        $this->assertFalse(NodeConfig::isTruthy(false));
        $this->assertFalse(NodeConfig::isTruthy(0));
        $this->assertFalse(NodeConfig::isTruthy('0'));
    }

    public function testRealityAndVlessDetection(): void
    {
        $cfg = [
            'enable_vless' => '1',
            'security' => 'reality',
            'enable_reality' => true,
            'flow' => 'xtls-rprx-vision',
            'offset_port_node' => 443,
            'host' => 'www.microsoft.com',
            'network' => 'tcp',
            'reality-opts' => [
                'public_key' => 'pubKEY',
                'private_key' => 'privKEY',
                'short_ids' => ['abcd'],
                'server_names' => ['www.microsoft.com'],
            ],
        ];

        $this->assertTrue(NodeConfig::isVless($cfg));
        $this->assertTrue(NodeConfig::isReality($cfg));
        $this->assertSame('reality', NodeConfig::security($cfg));
        $this->assertSame('xtls-rprx-vision', NodeConfig::flow($cfg));

        $reality = NodeConfig::realityClient($cfg);
        $this->assertSame('pubKEY', $reality['public_key']);
        $this->assertSame('abcd', $reality['short_id']);
        $this->assertSame('www.microsoft.com', $reality['server_name']);
    }

    public function testClashNormalizesLegacyPortsToMixedPort(): void
    {
        $clash = new Clash();
        $ref = new \ReflectionClass(Clash::class);
        $method = $ref->getMethod('normalizeLocalPorts');
        $method->setAccessible(true);

        $normalized = $method->invoke($clash, [
            'port' => 7890,
            'socks-port' => 7891,
            'allow-lan' => false,
            'mode' => 'Rule',
        ]);

        $this->assertSame(7890, $normalized['mixed-port']);
        $this->assertArrayNotHasKey('port', $normalized);
        $this->assertArrayNotHasKey('socks-port', $normalized);
        $this->assertFalse($normalized['allow-lan']);

        $alreadyMixed = $method->invoke($clash, [
            'mixed-port' => 7897,
            'port' => 7890,
            'socks-port' => 7891,
        ]);
        $this->assertSame(7897, $alreadyMixed['mixed-port']);
        $this->assertArrayNotHasKey('port', $alreadyMixed);
        $this->assertArrayNotHasKey('socks-port', $alreadyMixed);
    }

    public function testClashSubscriptionYamlUsesMixedPortOnly(): void
    {
        $clash = new Clash();
        $ref = new \ReflectionClass(Clash::class);
        $normalize = $ref->getMethod('normalizeLocalPorts');
        $normalize->setAccessible(true);

        $clash_config = $normalize->invoke($clash, [
            'port' => 7890,
            'socks-port' => 7891,
            'allow-lan' => false,
            'mode' => 'Rule',
        ]);

        $yaml = yaml_emit(
            array_merge($clash_config, [
                'proxies' => [],
                'proxy-groups' => [
                    [
                        'name' => 'Proxy',
                        'type' => 'select',
                        'proxies' => ['DIRECT'],
                    ],
                ],
            ]),
            YAML_UTF8_ENCODING
        );

        $this->assertStringContainsString('mixed-port', $yaml);
        $this->assertStringContainsString('7890', $yaml);
        $this->assertDoesNotMatchRegularExpression('/^port:/m', $yaml);
        $this->assertStringNotContainsString('socks-port', $yaml);
    }

    public function testClashEmitsVlessRealityForHiddify(): void
    {
        $_ENV['Clash_Config'] = [
            'port' => 7890,
            'mode' => 'rule',
        ];
        $_ENV['Clash_Group_Indexes'] = [0];
        $_ENV['Clash_Group_Config'] = [
            'proxy-groups' => [
                [
                    'name' => 'Proxy',
                    'type' => 'select',
                    'proxies' => ['DIRECT'],
                ],
            ],
        ];

        $user = new stdClass();
        $user->uuid = '11111111-1111-1111-1111-111111111111';
        $user->port = 10000;
        $user->passwd = 'pass';
        $user->method = 'aes-256-gcm';
        $user->class = 1;
        $user->node_group = 0;
        $user->is_admin = 1;

        $node = new stdClass();
        $node->name = 'VN-Reality';
        $node->server = '1.2.3.4';
        $node->sort = 11;
        $node->custom_config = json_encode([
            'offset_port_node' => 443,
            'enable_vless' => '1',
            'enable_reality' => true,
            'security' => 'reality',
            'network' => 'tcp',
            'flow' => 'xtls-rprx-vision',
            'fingerprint' => 'chrome',
            'reality-opts' => [
                'public_key' => 'PUBLIC',
                'short_ids' => ['deadbeef'],
                'server_names' => ['www.cloudflare.com'],
            ],
        ]);

        $clash = new Clash();
        $ref = new \ReflectionClass(Clash::class);
        $method = $ref->getMethod('buildV2Family');
        $method->setAccessible(true);
        $proxy = $method->invoke($clash, $node, $user, NodeConfig::decode($node->custom_config));
        $yaml = yaml_emit(['proxies' => [$proxy]], YAML_UTF8_ENCODING);
        $this->assertStringContainsString('vless', $yaml);
        $this->assertStringContainsString('PUBLIC', $yaml);
        $this->assertStringContainsString('reality-opts', $yaml);
        $this->assertStringContainsString('xtls-rprx-vision', $yaml);
        $this->assertStringNotContainsString("type: vmess", $yaml);
    }

    public function testSingBoxEmitsVlessReality(): void
    {
        $_ENV['SingBox_Config'] = [
            'outbounds' => [
                ['type' => 'selector', 'tag' => 'proxy', 'outbounds' => []],
                ['type' => 'urltest', 'tag' => 'auto', 'outbounds' => []],
            ],
            'experimental' => [
                'cache_file' => [
                    'enabled' => true,
                    'cache_id' => '',
                ],
            ],
        ];
        $_ENV['appName'] = 'DPanel';

        $user = new stdClass();
        $user->uuid = '22222222-2222-2222-2222-222222222222';

        $node = new stdClass();
        $node->name = 'SG-Reality';
        $node->server = '5.6.7.8';
        $node->sort = 11;
        $node->custom_config = json_encode([
            'offset_port_node' => 443,
            'enable_vless' => 1,
            'security' => 'reality',
            'network' => 'tcp',
            'reality-opts' => [
                'public_key' => 'SB_PUB',
                'short_ids' => ['01'],
                'server_names' => ['www.microsoft.com'],
            ],
        ]);

        $sb = new class () extends SingBox {
            public array $injectNodes = [];

            public function getContent($user): string
            {
                $ref = new \ReflectionClass(SingBox::class);
                $method = $ref->getMethod('buildV2Family');
                $method->setAccessible(true);
                $proxy = $method->invoke($this, $this->injectNodes[0], $user, \App\Services\Subscribe\NodeConfig::decode($this->injectNodes[0]->custom_config));

                return json_encode($proxy);
            }
        };
        $sb->injectNodes = [$node];

        $json = $sb->getContent($user);
        $data = json_decode($json, true);
        $this->assertSame('vless', $data['type']);
        $this->assertTrue($data['tls']['reality']['enabled']);
        $this->assertSame('SB_PUB', $data['tls']['reality']['public_key']);
        $this->assertSame('xtls-rprx-vision', $data['flow']);
    }

    public function testV2RayShareLinkIsVlessUri(): void
    {
        // ensure Config::obtain path not hit — enable_v2_sub via mock is complex;
        // test URI builder through reflection instead.
        $v2 = new V2Ray();
        $ref = new \ReflectionClass(V2Ray::class);
        $method = $ref->getMethod('buildVlessUri');
        $method->setAccessible(true);

        $node = new stdClass();
        $node->name = 'Test';
        $node->server = '9.9.9.9';
        $user = new stdClass();
        $user->uuid = '33333333-3333-3333-3333-333333333333';
        $cfg = [
            'flow' => 'xtls-rprx-vision',
            'fingerprint' => 'chrome',
            'reality-opts' => [
                'public_key' => 'PK',
                'short_ids' => ['aa'],
                'server_names' => ['www.apple.com'],
            ],
        ];

        $uri = $method->invoke($v2, $node, $user, $cfg, 443, 'reality', 'tcp', 'www.apple.com', '/');
        $this->assertStringStartsWith('vless://', $uri);
        $this->assertStringContainsString('security=reality', $uri);
        $this->assertStringContainsString('pbk=PK', $uri);
        $this->assertStringContainsString('flow=xtls-rprx-vision', $uri);
    }

    public function testHiddifyTrojanShareLinkUsesUuidPassword(): void
    {
        $user = new stdClass();
        $user->uuid = '44444444-4444-4444-4444-444444444444';
        $user->passwd = 'legacy-pass';

        $node = new stdClass();
        $node->name = 'VN-Trojan';
        $node->server = '10.0.0.1';
        $node->sort = 14;
        $node->custom_config = json_encode([
            'offset_port_node' => 443,
            'host' => 'cdn.example.com',
            'network' => 'tcp',
            'security' => 'tls',
            'fingerprint' => 'chrome',
        ]);

        $cfg = NodeConfig::decode($node->custom_config);
        $query = NodeConfig::trojanShareQuery($cfg, (string) $node->server);
        $link = 'trojan://' . rawurlencode(NodeConfig::trojanPassword($user)) . '@' . $node->server . ':'
            . NodeConfig::port($cfg) . '?' . http_build_query($query) . '#' . rawurlencode((string) $node->name);

        $this->assertStringStartsWith('trojan://', $link);
        $this->assertStringContainsString('44444444-4444-4444-4444-444444444444', $link);
        $this->assertStringContainsString('sni=cdn.example.com', $link);
        $this->assertStringContainsString('hiddify=1', $link);
        // Plain Trojan TCP must NOT force alpn — Clash Meta omits it and works;
        // forcing http/1.1 is a Hiddify-only upload disconnect risk on XrayR.
        $this->assertStringNotContainsString('alpn=', $link);
        $this->assertStringNotContainsString('headerType=', $link);
        // host is WS/CDN only; plain TCP uses sni alone.
        $this->assertStringNotContainsString('host=', $link);
        $this->assertStringNotContainsString('allowInsecure', $link);
        $this->assertStringNotContainsString('legacy-pass', $link);
    }

    public function testTrojanShareQueryMatchesHiddifyPanelShape(): void
    {
        $q = NodeConfig::trojanShareQuery([
            'offset_port_node' => 443,
            'host' => 'node.example.com',
            'network' => 'tcp',
            'security' => 'tls',
            'fingerprint' => 'chrome',
        ], 'node.example.com');

        $this->assertSame('1', $q['hiddify']);
        $this->assertArrayNotHasKey('alpn', $q);
        $this->assertArrayNotHasKey('headerType', $q);
        $this->assertArrayNotHasKey('host', $q);
        $this->assertSame('chrome', $q['fp']);
        $this->assertSame('tls', $q['security']);
        $this->assertArrayNotHasKey('allowInsecure', $q);
    }

    public function testTrojanShareQueryEmitsAlpnWhenConfiguredOrGrpc(): void
    {
        $explicit = NodeConfig::trojanShareQuery([
            'network' => 'tcp',
            'security' => 'tls',
            'alpn' => 'http/1.1',
            'host' => 'node.example.com',
        ], 'node.example.com');
        $this->assertSame('http/1.1', $explicit['alpn']);

        $grpc = NodeConfig::trojanShareQuery([
            'network' => 'grpc',
            'security' => 'tls',
            'host' => 'node.example.com',
            'servicename' => 'trojan',
        ], 'node.example.com');
        $this->assertSame('h2', $grpc['alpn']);
    }

    public function testHiddifyServesBase64ShareLinks(): void
    {
        $src = (string) file_get_contents(dirname(__DIR__, 3) . '/src/Services/Subscribe/Hiddify.php');
        $this->assertStringContainsString('base64_encode($links)', $src);
        $this->assertStringNotContainsString('new Clash()', $src);
    }

    public function testHiddifyDeepLinkUsesQueryUrlForm(): void
    {
        $file = dirname(__DIR__, 3) . '/config/client_display.json';
        $json = json_decode((string) file_get_contents($file), true);
        $this->assertSame('ClashMi', $json['clients'][0]['name'] ?? null);
        $hiddify = null;
        foreach ($json['clients'] as $c) {
            if (($c['name'] ?? '') === 'Hiddify') {
                $hiddify = $c;
                break;
            }
        }
        $this->assertNotNull($hiddify);
        $this->assertStringContainsString('hiddify://import/?url={url}', (string) $hiddify['importUrl']);
        $this->assertStringNotContainsString('hiddify://import/{sub}', (string) $hiddify['importUrl']);
    }
}
