<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Models\Node;
use App\Services\RateLimit;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use RedisException;
use Slim\Factory\AppFactory;
use voku\helper\AntiXSS;
use function parse_url;
use function preg_replace;
use function str_contains;
use function strtolower;
use function trim;
use const PHP_URL_HOST;

final class NodeToken implements MiddlewareInterface
{
    /**
     * @throws RedisException
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $key = $request->getQueryParams()['key'] ?? null;

        if ($key === null) {
            return AppFactory::determineResponseFactory()->createResponse(401)->withJson([
                'ret' => 0,
                'msg' => 'Invalid request.',
            ]);
        }

        $antiXss = new AntiXSS();

        if ($_ENV['enable_rate_limit'] &&
            (! (new RateLimit())->checkRateLimit('webapi_ip', $request->getServerParam('REMOTE_ADDR')) ||
                ! (new RateLimit())->checkRateLimit('webapi_key', $antiXss->xss_clean($key)))
        ) {
            return AppFactory::determineResponseFactory()->createResponse(429)->withJson([
                'ret' => 0,
                'msg' => 'Rate limit exceeded.',
            ]);
        }

        $requestHost = strtolower(trim($request->getHeaderLine('Host')));
        if (str_contains($requestHost, ':')) {
            $requestHost = explode(':', $requestHost, 2)[0];
        }

        $expectedHost = strtolower((string) (parse_url((string) ($_ENV['webAPIUrl'] ?? ''), PHP_URL_HOST) ?: ''));
        if ($expectedHost === '' && isset($_ENV['webAPIUrl'])) {
            $expectedHost = strtolower(trim((string) $_ENV['webAPIUrl']));
            $expectedHost = preg_replace('#^https?://#', '', $expectedHost) ?? $expectedHost;
            if (str_contains($expectedHost, '/')) {
                $expectedHost = explode('/', $expectedHost, 2)[0];
            }
            if (str_contains($expectedHost, ':')) {
                $expectedHost = explode(':', $expectedHost, 2)[0];
            }
        }

        if (! $_ENV['webAPI'] ||
            $key !== $_ENV['muKey'] ||
            $requestHost === '' ||
            $expectedHost === '' ||
            $requestHost !== $expectedHost
        ) {
            return AppFactory::determineResponseFactory()->createResponse(401)->withJson([
                'ret' => 0,
                'msg' => 'Invalid request.',
            ]);
        }

        if ($_ENV['checkNodeIp'] ?? false) {
            $ip = $request->getServerParam('REMOTE_ADDR');

            if ($ip !== '127.0.0.1' && $ip !== '::1' && $ip !== '0:0:0:0:0:0:0:1' &&
                ! (new Node())->where('ipv4', $ip)->orWhere('ipv6', $ip)->exists()
            ) {
                return AppFactory::determineResponseFactory()->createResponse(401)->withJson([
                    'ret' => 0,
                    'msg' => 'Invalid request IP.',
                ]);
            }
        }

        return $handler->handle($request);
    }
}
