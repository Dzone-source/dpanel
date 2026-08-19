<?php

declare(strict_types=1);

namespace App\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use GuzzleHttp\Psr7\Response;
use function in_array;
use function parse_url;
use function str_starts_with;
use function strtolower;

/**
 * Validates Origin/Referer on state-changing requests to mitigate CSRF.
 */
final class Csrf implements MiddlewareInterface
{
    private const SAFE_METHODS = ['GET', 'HEAD', 'OPTIONS'];

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $method = strtoupper($request->getMethod());

        if (in_array($method, self::SAFE_METHODS, true)) {
            return $handler->handle($request);
        }

        $path = $request->getUri()->getPath();

        if (str_starts_with($path, '/payment/notify/')
            || str_starts_with($path, '/callback/')
            || str_starts_with($path, '/mod_mu/')
        ) {
            return $handler->handle($request);
        }

        $base_host = parse_url((string) ($_ENV['baseUrl'] ?? ''), PHP_URL_HOST);

        if ($base_host === null || $base_host === '') {
            return $handler->handle($request);
        }

        $origin = $request->getHeaderLine('Origin');
        $referer = $request->getHeaderLine('Referer');

        if ($origin !== '') {
            $origin_host = parse_url($origin, PHP_URL_HOST);
            if ($origin_host !== $base_host) {
                return $this->forbidden();
            }
        } elseif ($referer !== '') {
            $referer_host = parse_url($referer, PHP_URL_HOST);
            if ($referer_host !== $base_host) {
                return $this->forbidden();
            }
        }

        return $handler->handle($request);
    }

    private function forbidden(): ResponseInterface
    {
        $response = new Response(403);
        $response->getBody()->write('Forbidden');

        return $response;
    }
}
