<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\Product;
use App\Services\Auth;
use Psr\Http\Message\ResponseInterface;
use Slim\Http\Response;
use Slim\Http\ServerRequest;
use Smarty\Exception as SmartyException;
use function json_decode;

final class HomeController extends BaseController
{
    /**
     * Public marketing landing. Auth / user area unchanged.
     *
     * @throws SmartyException
     */
    public function index(ServerRequest $request, Response $response, array $args): ResponseInterface
    {
        if ($this->user->isLogin) {
            return $response->withRedirect('/user');
        }

        // Prefer combo packages for pricing; fall back to any on-sale product.
        $tabps = (new Product())->where('status', '1')
            ->where('type', 'tabp')
            ->orderBy('price')
            ->orderBy('id')
            ->get();

        if ($tabps->isEmpty()) {
            $tabps = (new Product())->where('status', '1')
                ->whereIn('type', ['time', 'bandwidth'])
                ->orderBy('price')
                ->orderBy('id')
                ->get();
        }

        foreach ($tabps as $product) {
            $product->content = json_decode((string) $product->content);
        }

        return $response->write(
            $this->view()
                ->assign('pricing_products', $tabps)
                ->fetch('index.tpl')
        );
    }

    /**
     * @throws SmartyException
     */
    public function tos(ServerRequest $request, Response $response, array $args): ResponseInterface
    {
        return $response->write($this->view()->fetch('tos.tpl'));
    }

    /**
     * @throws SmartyException
     */
    public function staff(ServerRequest $request, Response $response, array $args): ResponseInterface
    {
        $user = Auth::getUser();

        if (! $user->isLogin) {
            return $response->withStatus(404)->write($this->view()->fetch('404.tpl'));
        }

        return $response->write($this->view()->fetch('staff.tpl'));
    }

    /**
     * @throws SmartyException
     */
    public function notFound(ServerRequest $request, Response $response, array $args): ResponseInterface
    {
        return $response->write($this->view()->fetch('404.tpl'));
    }

    /**
     * @throws SmartyException
     */
    public function methodNotAllowed(ServerRequest $request, Response $response, array $args): ResponseInterface
    {
        return $response->write($this->view()->fetch('405.tpl'));
    }

    /**
     * @throws SmartyException
     */
    public function internalServerError(ServerRequest $request, Response $response, array $args): ResponseInterface
    {
        return $response->write($this->view()->fetch('500.tpl'));
    }
}
