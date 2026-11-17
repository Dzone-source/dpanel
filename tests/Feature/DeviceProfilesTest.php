<?php

declare(strict_types=1);

use App\Controllers\DeviceController;
use App\Controllers\SubController;
use App\Controllers\WebAPI\UserController;
use App\Models\Node;
use App\Models\User;
use App\Models\UserDevice;
use App\Services\DB;
use App\Services\DeviceProfiles;
use App\Services\Subscribe;
use GuzzleHttp\Psr7\HttpFactory;
use Illuminate\Database\Schema\Blueprint;
use PHPUnit\Framework\TestCase;
use Slim\Http\Response;
use Slim\Http\ServerRequest;

final class DeviceProfilesTest extends TestCase
{
    private User $owner;
    private Node $node;

    protected function setUp(): void
    {
        $db = new DB();
        $mysql = getenv('DEVICE_TEST_MYSQL') === '1';
        $db->addConnection($mysql
            ? ['driver' => 'mysql', 'host' => 'dpanel-device-db', 'database' => 'device_profiles_test', 'username' => 'root', 'password' => 'device-test-only', 'charset' => 'utf8mb4', 'collation' => 'utf8mb4_unicode_ci', 'prefix' => '']
            : ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '']);
        $db->setAsGlobal();
        $db->bootEloquent();
        $schema = $db->schema();
        if ($mysql) {
            $schema->dropAllTables();
        }
        $schema->create('user', function (Blueprint $t) use ($mysql): void {
            $t->increments('id');
            foreach (['u', 'd', 'transfer_enable', 'transfer_total', 'transfer_today', 'last_use_time', 'node_iplimit', 'node_speedlimit', 'class', 'is_admin', 'is_banned', 'node_group', 'device_profiles_enabled', 'device_limit', 'port'] as $field) {
                if (! $mysql || ! in_array($field, ['device_profiles_enabled', 'device_limit'], true)) {
                    $t->integer($field)->default($field === 'device_limit' ? 3 : 0);
                }
            }
            foreach (['uuid', 'passwd', 'method', 'pass', 'email', 'user_name', 'class_expire'] as $field) {
                $t->string($field)->default('');
            }
        });
        if ($mysql) {
            (require dirname(__DIR__, 2) . '/db/migrations/2026111700-add_device_profiles.php')->up();
        } else {
            $schema->create('user_device', function (Blueprint $t): void {
                $t->increments('id');
                $t->integer('user_id');
                $t->string('name');
                $t->string('uuid')->unique();
                $t->string('password');
                $t->string('token')->unique();
                $t->integer('created_at');
                $t->integer('revoked_at')->nullable();
                $t->integer('last_seen_at')->nullable();
            });
            $schema->create('user_device_presence', function (Blueprint $t): void {
                $t->increments('id');
                $t->integer('device_id');
                $t->integer('node_id');
                $t->string('ip');
                $t->integer('last_seen_at');
                $t->integer('connections');
                $t->unique(['device_id', 'node_id', 'ip']);
            });
        }
        $schema->create('node', function (Blueprint $t): void {
            $t->increments('id');
            foreach (['type', 'sort', 'node_class', 'node_group', 'node_heartbeat', 'node_bandwidth', 'node_bandwidth_limit', 'node_speedlimit', 'is_dynamic_rate', 'online_user'] as $field) {
                $t->integer($field)->default(0);
            }
            $t->integer('traffic_rate')->default(1);
            foreach (['name', 'server', 'custom_config'] as $field) {
                $t->string($field)->default('');
            }
        });
        $schema->create('online_log', function (Blueprint $t): void {
            $t->increments('id');
            $t->integer('user_id');
            $t->integer('node_id');
            $t->string('ip');
            $t->integer('first_time');
            $t->integer('last_time');
            $t->unique(['user_id', 'ip']);
        });
        $schema->create('config', function (Blueprint $t): void {
            $t->increments('id');
            $t->string('item');
            $t->string('value');
            $t->string('type');
        });
        $schema->create('link', function (Blueprint $t): void {
            $t->increments('id');
            $t->integer('userid');
            $t->string('token');
        });
        $this->owner = User::create(['email' => 'owner@example.test', 'uuid' => '11111111-1111-4111-8111-111111111111', 'passwd' => 'account-fixture', 'pass' => 'login-fixture', 'class' => 1, 'class_expire' => date('Y-m-d H:i:s', time() + 3600), 'transfer_enable' => 1000000, 'device_profiles_enabled' => 1, 'device_limit' => 2]);
        $this->node = Node::create(['type' => 1, 'sort' => 11, 'name' => 'Node', 'server' => 'node.test', 'custom_config' => '{"offset_port_node":443,"network":"tcp","enable_vless":true,"security":"reality"}']);
        $GLOBALS['user'] = $this->owner;
        $_ENV = ['key' => 'test-csrf-key', 'subUrl' => 'https://panel.test', 'baseUrl' => 'https://panel.test', 'Subscribe' => true, 'enable_rate_limit' => false, 'appName' => 'Test'];
    }

    private function request(string $method = 'GET', array $query = [], array $body = []): ServerRequest
    {
        $factory = new HttpFactory();
        return (new ServerRequest($factory->createServerRequest($method, 'https://panel.test/')->withHeader('Host', 'panel.test')))->withQueryParams($query)->withParsedBody($body);
    }
    private function response(): Response
    {
        $f = new HttpFactory();
        return new Response($f->createResponse(), $f);
    }
    private function nodeUsers(bool $capable = true): array
    {
        $r = (new UserController())->index($this->request('GET', ['node_id' => $this->node->id, 'device_profiles' => $capable ? '1' : '0']), $this->response(), []);
        return json_decode((string) $r->getBody(), true)['data'];
    }

    public function testSeparateCredentialsSameAccountAndRevocation(): void
    {
        $a = DeviceProfiles::create($this->owner->id, 'Phone');
        $b = DeviceProfiles::create($this->owner->id, 'Laptop');
        self::assertNotSame($a->uuid, $b->uuid);
        $users = $this->nodeUsers();
        self::assertCount(2, $users);
        self::assertSame([$this->owner->id, $this->owner->id], array_column($users, 'id'));
        self::assertSame([$a->id, $b->id], array_column($users, 'device_id'));
        self::assertNotContains($this->owner->uuid, array_column($users, 'uuid'));
        self::assertSame([], $this->nodeUsers(false));
        self::assertTrue(DeviceProfiles::revoke($this->owner->id, $a->id));
        self::assertNull(DeviceProfiles::subscription($a->token));
        self::assertCount(1, $this->nodeUsers());
        self::assertSame($b->uuid, DeviceProfiles::subscription($b->token)->uuid);
        self::assertSame($b->subscriptionUrl(), Subscribe::getUniversalSubLink(DeviceProfiles::subscription($b->token)));
    }

    public function testRegistrationLimitAndOwnership(): void
    {
        $a = DeviceProfiles::create($this->owner->id, 'First');
        DeviceProfiles::create($this->owner->id, 'Second');
        self::assertFalse(DeviceProfiles::revoke($this->owner->id + 1, $a->id));
        $this->expectException(DomainException::class);
        DeviceProfiles::create($this->owner->id, 'Third');
    }

    public function testAccountBanAndExpiryRemoveAllCredentials(): void
    {
        $a = DeviceProfiles::create($this->owner->id, 'Phone');
        $this->owner->update(['is_banned' => 1]);
        self::assertSame([], $this->nodeUsers());
        self::assertNull(DeviceProfiles::subscription($a->token));
        $this->owner->update(['is_banned' => 0, 'class_expire' => date('Y-m-d H:i:s', time() - 60)]);
        self::assertSame([], $this->nodeUsers());
        self::assertNull(DeviceProfiles::subscription($a->token));
    }

    public function testLegacyAccountsRemainCompatible(): void
    {
        $this->owner->update(['device_profiles_enabled' => 0]);
        $users = $this->nodeUsers(false);
        self::assertCount(1, $users);
        self::assertSame($this->owner->uuid, $users[0]['uuid']);
    }

    public function testCsrfAndForeignDeviceCannotBeRevoked(): void
    {
        $device = DeviceProfiles::create($this->owner->id, 'Phone');
        $r = (new DeviceController())->change($this->request('POST', [], ['action' => 'revoke', 'device_id' => $device->id, 'csrf' => 'wrong']), $this->response(), []);
        self::assertSame(403, $r->getStatusCode());
        self::assertNull($device->fresh()->revoked_at);
    }

    public function testPresenceSeparatesSameIpDevicesAndEmptySnapshotClears(): void
    {
        $a = DeviceProfiles::create($this->owner->id, 'Phone');
        $b = DeviceProfiles::create($this->owner->id, 'Laptop');
        $payload = json_encode(['data' => [['user_id' => $this->owner->id, 'device_id' => $a->id, 'ip' => '192.0.2.1', 'connections' => 2], ['user_id' => $this->owner->id, 'device_id' => $b->id, 'ip' => '192.0.2.1']]]);
        $request = $this->request('POST', ['node_id' => $this->node->id, 'device_profiles' => '1']);
        $request->getBody()->write($payload);
        (new UserController())->addAliveIp($request, $this->response(), []);
        self::assertSame(2, DB::table('user_device_presence')->count());
        $request = $this->request('POST', ['node_id' => $this->node->id, 'device_profiles' => '1']);
        $request->getBody()->write('{"data":[]}');
        (new UserController())->addAliveIp($request, $this->response(), []);
        self::assertSame(0, DB::table('user_device_presence')->count());
    }
    public function testGeneratedSubscriptionUsesOnlySelectedDeviceCredential(): void
    {
        $a = DeviceProfiles::create($this->owner->id, 'Phone');
        $b = DeviceProfiles::create($this->owner->id, 'Laptop');
        $_ENV['Clash_Config'] = [];
        $_ENV['Clash_Group_Indexes'] = [];
        $_ENV['Clash_Group_Config'] = [];
        $request = $this->request()->withHeader('Host', 'panel.test');
        $r = (new SubController())->index($request, $this->response(), ['device_token' => $a->token, 'subtype' => 'clash']);
        self::assertSame(200, $r->getStatusCode());
        $content = (string) $r->getBody();
        self::assertStringContainsString($a->uuid, $content);
        self::assertStringNotContainsString($b->uuid, $content);
        self::assertStringNotContainsString($this->owner->uuid, $content);
        DeviceProfiles::revoke($this->owner->id, $a->id);
        $r = (new SubController())->index($request, $this->response(), ['device_token' => $a->token, 'subtype' => 'clash']);
        self::assertSame(403, $r->getStatusCode());
    }

    public function testTrafficIsBilledToAccountAndMismatchedDeviceIsRejected(): void
    {
        $a = DeviceProfiles::create($this->owner->id, 'Phone');
        $b = DeviceProfiles::create($this->owner->id, 'Laptop');
        $request = $this->request('POST', ['node_id' => $this->node->id]);
        $request->getBody()->write(json_encode(['data' => [
            ['user_id' => $this->owner->id, 'device_id' => $a->id, 'u' => 10, 'd' => 20],
            ['user_id' => $this->owner->id, 'device_id' => $b->id, 'u' => 30, 'd' => 40],
            ['user_id' => $this->owner->id, 'device_id' => 9999, 'u' => 1000, 'd' => 1000],
        ]]));
        (new UserController())->addTraffic($request, $this->response(), []);
        self::assertSame(40, (int) $this->owner->fresh()->u);
        self::assertSame(60, (int) $this->owner->fresh()->d);
    }

    public function testValidCsrfOwnerRevokesAndNormalUserCannotUseAdminAction(): void
    {
        $a = DeviceProfiles::create($this->owner->id, 'Phone');
        $request = $this->request('POST', [], ['csrf' => DeviceProfiles::csrf($this->owner), 'action' => 'revoke', 'device_id' => $a->id]);
        $r = (new DeviceController())->adminRevoke($request, $this->response(), ['id' => $this->owner->id, 'device_id' => $a->id]);
        self::assertSame(403, $r->getStatusCode());
        $r = (new DeviceController())->change($request, $this->response(), []);
        self::assertSame(303, $r->getStatusCode());
        self::assertNotNull($a->fresh()->revoked_at);
    }

    public function testDeviceTemplateEscapesNamesAndDashboardTemplatesCompile(): void
    {
        $dir = sys_get_temp_dir() . '/device-template-' . bin2hex(random_bytes(8));
        mkdir($dir . '/user', 0700, true);
        mkdir($dir . '/compile', 0700);
        file_put_contents($dir . '/user/header.tpl', '');
        file_put_contents($dir . '/user/footer.tpl', '');
        $smarty = new \Smarty\Smarty();
        $smarty->setTemplateDir([$dir, dirname(__DIR__, 2) . '/resources/views/tabler']);
        $smarty->setCompileDir($dir . '/compile');
        $smarty->assign('notice', '')->assign('csrf', 'test')->assign('device_mode', true)->assign('device_limit', 2)
            ->assign('devices', [['id' => 1, 'name' => '<script>bad()</script>', 'revoked' => false, 'online' => true, 'last_seen' => 'Today', 'ips' => '192.0.2.1', 'connections' => 2, 'url' => 'https://panel.test/device-sub/test/clash']]);
        $html = $smarty->fetch('user/devices.tpl');
        self::assertStringNotContainsString('<script>bad()', $html);
        self::assertStringContainsString('&lt;script&gt;', $html);
        $smarty->createTemplate('user/index.tpl')->compileTemplateSource();
        $smarty->createTemplate('admin/user/edit.tpl')->compileTemplateSource();
    }

    public function testMariaDbMigrationCanRollBackAndReapply(): void
    {
        if (getenv('DEVICE_TEST_MYSQL') !== '1') {
            self::markTestSkipped('Run with DEVICE_TEST_MYSQL=1 against the isolated MariaDB fixture.');
        }
        $migration = require dirname(__DIR__, 2) . '/db/migrations/2026111700-add_device_profiles.php';
        self::assertSame(2025073100, $migration->down());
        self::assertFalse(DB::schema()->hasTable('user_device'));
        self::assertSame(2026111700, $migration->up());
        self::assertTrue(DB::schema()->hasTable('user_device'));
    }

    public function testConcurrentRegistrationsCannotExceedLimit(): void
    {
        if (getenv('DEVICE_TEST_MYSQL') !== '1') {
            self::markTestSkipped('Requires the isolated MariaDB fixture and row locking.');
        }
        $this->owner->update(['device_limit' => 1]);
        $processes = [];
        for ($i = 0; $i < 2; ++$i) {
            $processes[] = proc_open([PHP_BINARY, dirname(__DIR__) . '/Fixtures/device_profile_worker.php', (string) $this->owner->id], [], $pipes);
        }
        $codes = array_map(static fn ($process) => proc_close($process), $processes);
        sort($codes);
        self::assertSame([0, 10], $codes);
        self::assertSame(1, UserDevice::where('user_id', $this->owner->id)->whereNull('revoked_at')->count());
    }
    public function testInvalidAdminDeviceLimitIsRejectedBeforeOtherChanges(): void
    {
        $request = $this->request('POST', [], ['device_limit' => -1, 'money' => 100]);
        $r = (new \App\Controllers\Admin\UserController())->update($request, $this->response(), ['id' => $this->owner->id]);
        self::assertSame(0, json_decode((string) $r->getBody(), true)['ret']);
        self::assertSame(2, (int) $this->owner->fresh()->device_limit);
    }

}
