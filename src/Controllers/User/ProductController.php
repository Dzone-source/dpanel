<?php

declare(strict_types=1);

namespace App\Controllers\User;

use App\Controllers\BaseController;
use App\Models\Product;
use Exception;
use Psr\Http\Message\ResponseInterface;
use Slim\Http\Response;
use Slim\Http\ServerRequest;
use function json_decode;

final class ProductController extends BaseController
{
    /**
     * @throws Exception
     */
    public function index(ServerRequest $request, Response $response, array $args): ResponseInterface
    {
        $tabps = (new Product())->where('status', '1')
            ->where('type', 'tabp')
            ->orderBy('id')
            ->get();

        $bandwidths = (new Product())->where('status', '1')
            ->where('type', 'bandwidth')
            ->orderBy('id')
            ->get();

        $times = (new Product())->where('status', '1')
            ->where('type', 'time')
            ->orderBy('id')
            ->get();

        foreach ($tabps as $tabp) {
            $tabp->content = json_decode($tabp->content);
            $tabp->options = Product::normalizeOptions($tabp->content);
            $tabp->has_options = $tabp->options !== [];
            if ($tabp->has_options) {
                $prices = array_column($tabp->options, 'price');
                $tabp->price_min = min($prices);
                $tabp->price_max = max($prices);
            } else {
                $tabp->price_min = (float) $tabp->price;
                $tabp->price_max = (float) $tabp->price;
            }
        }

        foreach ($bandwidths as $bandwidth) {
            $bandwidth->content = json_decode($bandwidth->content);
            $bandwidth->options = [];
            $bandwidth->has_options = false;
            $bandwidth->price_min = (float) $bandwidth->price;
            $bandwidth->price_max = (float) $bandwidth->price;
        }

        foreach ($times as $time) {
            $time->content = json_decode($time->content);
            $time->options = Product::normalizeOptions($time->content);
            $time->has_options = $time->options !== [];
            if ($time->has_options) {
                $prices = array_column($time->options, 'price');
                $time->price_min = min($prices);
                $time->price_max = max($prices);
            } else {
                $time->price_min = (float) $time->price;
                $time->price_max = (float) $time->price;
            }
        }

        return $response->write(
            $this->view()
                ->assign('tabps', $tabps)
                ->assign('bandwidths', $bandwidths)
                ->assign('times', $times)
                ->fetch('user/product.tpl')
        );
    }
}
