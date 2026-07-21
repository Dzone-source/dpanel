<?php

declare(strict_types=1);

/**
 * @group database
 * @group feature
 * @group api
 */

use App\Models\Node;
use App\Models\OnlineLog;
use App\Models\User;

beforeEach(function () {
    // Clear previous test data
    Node::where('name', 'Test Node')->delete();
    User::where('email', 'LIKE', 'test%@example.com')->delete();
    
    // Create test node
    $this->node = new Node();
    $this->node->name = 'Test Node';
    $this->node->server = 'test.example.com';
    $this->node->password = bin2hex(random_bytes(32));
    $this->node->type = 1;
    $this->node->sort = 14;
    $this->node->node_class = 0; // Allow class 0 users
    $this->node->node_group = 0; // No group restriction
    $this->node->save();
});

afterEach(function () {
    if (isset($this->node)) {
        OnlineLog::where('node_id', $this->node->id)->delete();
        $this->node->delete();
    }
    User::where('email', 'LIKE', 'test%@example.com')->delete();
});

describe('UserController API - Trojan Node', function () {
    it('returns user list for trojan node', function () {
        // Create test users
        $users = createUsers(3);
        
        // Send request
        $response = $this->get('/mod_mu/users?node_id=' . $this->node->id . '&key=' . $_ENV['muKey']);
        
        // Verify response
        assertResponseStatus(200, $response);
        assertJsonResponse($response);
        
        $data = getJsonData($response);
        expect($data['ret'])->toBe(1);
        
        foreach ($users as $user) {
            $userData = findUserData($data['data'], $user->id);

            expect($userData)
                ->not->toHaveKeys(['u', 'd', 'transfer_enable', 'method', 'port', 'passwd'])
                ->toHaveKeys(['id', 'uuid', 'node_speedlimit', 'node_iplimit', 'alive_ip'])
                ->and($userData['node_iplimit'])->toBe(0)
                ->and($userData['alive_ip'])->toBe(0);
        }
    });
});

describe('UserController API - IP online limit', function () {
    it('returns node_iplimit and alive_ip for xrayr device limiting', function () {
        $user = createUsers(1)[0];
        $user->node_iplimit = 2;
        $user->save();

        OnlineLog::upsert(
            [
                'user_id' => $user->id,
                'ip' => '::ffff:1.1.1.1',
                'node_id' => $this->node->id,
                'first_time' => time(),
                'last_time' => time(),
            ],
            ['user_id', 'ip'],
            ['node_id', 'last_time']
        );

        $response = $this->get('/mod_mu/users?node_id=' . $this->node->id . '&key=' . $_ENV['muKey']);
        assertResponseStatus(200, $response);

        $userData = findUserData(getJsonData($response)['data'], $user->id);
        expect($userData['node_iplimit'])->toBe(2)
            // 1 raw online IP − 1 grace slot for SoftBank/NAT rebind
            ->and($userData['alive_ip'])->toBe(0);
    });

    it('keeps over-limit users in the list so XrayR can soft-kick instead of timing out', function () {
        $user = createUsers(1)[0];
        $user->node_iplimit = 1;
        $user->save();

        foreach (['::ffff:1.1.1.1', '::ffff:2.2.2.2'] as $ip) {
            OnlineLog::upsert(
                [
                    'user_id' => $user->id,
                    'ip' => $ip,
                    'node_id' => $this->node->id,
                    'first_time' => time(),
                    'last_time' => time(),
                ],
                ['user_id', 'ip'],
                ['node_id', 'last_time']
            );
        }

        $response = $this->get('/mod_mu/users?node_id=' . $this->node->id . '&key=' . $_ENV['muKey']);
        assertResponseStatus(200, $response);

        $userData = findUserData(getJsonData($response)['data'], $user->id);
        expect($userData)->not->toBeNull()
            ->and($userData['node_iplimit'])->toBe(1)
            // 2 raw IPs − 1 grace = 1 reported to XrayR
            ->and($userData['alive_ip'])->toBe(1);
    });

    it('accepts alive ip reports from nodes', function () {
        $user = createUsers(1)[0];

        $response = $this->post('/mod_mu/users/aliveip?node_id=' . $this->node->id . '&key=' . $_ENV['muKey'], [
            'data' => [
                [
                    'user_id' => $user->id,
                    'ip' => '8.8.8.8',
                ],
            ],
        ]);

        assertResponseStatus(200, $response);
        expect(getJsonData($response)['msg'])->toBe('ok');

        $log = OnlineLog::where('user_id', $user->id)->first();
        expect($log)->not->toBeNull()
            ->and($log->ip())->toBe('8.8.8.8')
            ->and((int) $log->node_id)->toBe((int) $this->node->id);
    });
});

describe('UserController API - Shadowsocks 2022 Node', function () {
    it('returns user list for shadowsocks 2022 node', function () {
        // Update node type
        $this->node->sort = 1;
        $this->node->save();
        
        // Create test users
        $sourceUser = createUsers(1)[0];
        $sourceUser->passwd = 'password';
        $sourceUser->save();
        
        // Send request
        $response = $this->get('/mod_mu/users?node_id=' . $this->node->id . '&key=' . $_ENV['muKey']);
        
        // Verify response
        assertResponseStatus(200, $response);
        assertJsonResponse($response);
        
        $data = getJsonData($response);
        expect($data['ret'])->toBe(1);
        
        // Verify Shadowsocks 2022 returns a derived user password
        $userData = findUserData($data['data'], $sourceUser->id);
        expect($userData)
            ->toHaveKey('passwd')
            ->not->toHaveKey('uuid')
            ->and($userData['passwd'])->toBe('YzAwNjdkNGFmNGU4N2YwMA==')
            ->not->toBe($sourceUser->passwd);
    });
});

describe('UserController API Authentication', function () {
    it('rejects invalid node key', function () {
        // Use wrong key
        $response = $this->get('/mod_mu/users?node_id=' . $this->node->id . '&key=invalid_key');
        
        // Verify returns 401
        assertResponseStatus(401, $response);
    });
});

describe('UserController API Traffic Reporting', function () {
    it('updates user traffic', function () {
        // Create test user
        $user = createUsers(1)[0];
        $initialU = $user->u;
        $initialD = $user->d;
        
        // Report traffic
        $trafficData = [
            [
                'user_id' => $user->id,
                'u' => 1024 * 1024, // 1MB
                'd' => 2048 * 1024, // 2MB
            ],
        ];
        
        $response = $this->post('/mod_mu/users/traffic?node_id=' . $this->node->id . '&key=' . $_ENV['muKey'], [
            'data' => $trafficData,
        ]);
        
        // Verify response
        assertResponseStatus(200, $response);
        assertJsonResponse($response);
        
        $data = getJsonData($response);
        expect($data['ret'])->toBe(1)
            ->and($data['msg'])->toBe('ok');
        
        // Verify traffic update
        $user->refresh();
        expect($user->u)->toBe($initialU + 1024 * 1024)
            ->and($user->d)->toBe($initialD + 2048 * 1024);
    });
});

describe('UserController API Caching', function () {
    it('supports etag caching', function () {
        createUsers(1);

        // First request
        $response1 = $this->get('/mod_mu/users?node_id=' . $this->node->id . '&key=' . $_ENV['muKey']);
        
        assertResponseStatus(200, $response1);
        $etag = $response1->getHeaderLine('ETag');
        expect($etag)->not->toBeEmpty();
        
        // Second request with ETag
        $response2 = $this->get('/mod_mu/users?node_id=' . $this->node->id . '&key=' . $_ENV['muKey'], [
            'If-None-Match' => $etag,
        ]);
        
        // Should return 304
        assertResponseStatus(304, $response2);
    });
});

// Helper functions specific to this test file

/**
 * Assert response status code
 */
if (!function_exists('assertResponseStatus')) {
    function assertResponseStatus(int $expected, $response): void
    {
        expect($response->getStatusCode())->toBe($expected);
    }
}

/**
 * Get JSON data from response
 */
if (!function_exists('getJsonData')) {
    function getJsonData($response): array
    {
        return json_decode((string) $response->getBody(), true);
    }
}

/**
 * Create test users for API tests
 */
function createUsers(int $count): array
{
    $users = [];
    for ($i = 0; $i < $count; $i++) {
        $user = new User();
        $user->email = "test{$i}@example.com";
        $user->user_name = "testuser{$i}";
        $user->pass = password_hash('password', PASSWORD_DEFAULT);
        $user->api_token = bin2hex(random_bytes(32)); // Add unique API token
        $user->port = 10000 + $i;
        $user->passwd = bin2hex(random_bytes(16));
        $user->uuid = \Ramsey\Uuid\Uuid::uuid4()->toString();
        $user->method = 'aes-256-gcm';
        $user->transfer_enable = 1099511627776; // 1TB
        $user->u = rand(0, 1000000);
        $user->d = rand(0, 1000000);
        $user->node_iplimit = 0;
        $user->node_speedlimit = 0;
        $user->node_group = 0;
        $user->class = 0;
        $user->is_banned = 0;
        $user->class_expire = date('Y-m-d H:i:s', strtotime('+1 year'));
        $user->save();
        $users[] = $user;
    }
    return $users;
}

function findUserData(array $users, int $id): array
{
    foreach ($users as $user) {
        if ($user['id'] === $id) {
            return $user;
        }
    }

    throw new RuntimeException("User {$id} was not returned by the API.");
}
