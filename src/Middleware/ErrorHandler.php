<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Services\Auth as AuthService;
use App\Services\View;
use Exception;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\CallableResolver;
use Slim\Exception\HttpMethodNotAllowedException;
use Slim\Exception\HttpNotFoundException;
use Slim\Factory\AppFactory;
use Slim\Handlers\ErrorHandler as SlimErrorHandler;
use Throwable;
use function Sentry\captureException;

final class ErrorHandler implements MiddlewareInterface
{
    /**
     * @throws Exception
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $user = AuthService::getUser();
        $path = $request->getUri()->getPath();

        if (str_contains($path, '/admin') && ! $user->is_admin) {
            $response_factory = AppFactory::determineResponseFactory();
            $response = $response_factory->createResponse(302);

            if ($user->isLogin) {
                return $response->withHeader('Location', '/user');
            }

            return $response->withHeader('Location', '/auth/login');
        }

        try {
            $response = $handler->handle($request);
        } catch (HttpNotFoundException | HttpMethodNotAllowedException $e) {
            // 404 or 405 thrown by router
            $code = $e->getCode();
            $response_factory = AppFactory::determineResponseFactory();
            $response = $response_factory->createResponse($code);
            $smarty = View::getSmarty();
            $response->getBody()->write($smarty->fetch("{$code}.tpl"));
            $response = $response->withStatus($code);
        } catch (Throwable $e) {
            $response_factory = AppFactory::determineResponseFactory();

            if ($_ENV['sentry_dsn'] !== '') {
                captureException($e);
            }

            $wantsJson = str_contains((string) $request->getHeaderLine('Accept'), 'application/json')
                || $request->getHeaderLine('HX-Request') === 'true'
                || $request->getHeaderLine('X-Requested-With') === 'XMLHttpRequest';

            if ($wantsJson) {
                $response = $response_factory->createResponse(500);
                $response->getBody()->write(json_encode([
                    'ret' => 0,
                    'msg' => 'Lỗi máy chủ: ' . $e->getMessage(),
                ], JSON_UNESCAPED_UNICODE));

                return $response->withHeader('Content-Type', 'application/json');
            }

            if ($_ENV['debug']) {
                $callable_resolver = new CallableResolver(null);
                $error_handler = new SlimErrorHandler($callable_resolver, $response_factory);
                $response = $error_handler($request, $e, true, true, false);
            } else {
                $response = $response_factory->createResponse(500);
                $smarty = View::getSmarty();
                $response->getBody()->write($smarty->fetch('500.tpl'));
            }
        }

        return $response;
    }
}
