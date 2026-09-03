<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\Product;
use App\Services\Auth;
use Psr\Http\Message\ResponseInterface;
use Slim\Http\Response;
use Slim\Http\ServerRequest;
use Smarty\Exception as SmartyException;
use Throwable;
use function is_object;
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

        $pricing = [];

        try {
            $products = (new Product())->where('status', '1')
                ->where('type', 'tabp')
                ->orderBy('price')
                ->orderBy('id')
                ->get();

            if ($products->isEmpty()) {
                $products = (new Product())->where('status', '1')
                    ->whereIn('type', ['time', 'bandwidth'])
                    ->orderBy('price')
                    ->orderBy('id')
                    ->get();
            }

            $count = $products->count();
            $index = 0;

            foreach ($products as $product) {
                $content = json_decode((string) $product->content);
                if (! is_object($content)) {
                    $content = (object) [];
                }

                $features = [];

                if (isset($content->class) && $content->class !== '' && $content->class !== null) {
                    $features[] = 'Cấp độ thành viên Lv. ' . $content->class;
                }
                if (isset($content->class_time) && $content->class_time !== '' && $content->class_time !== null) {
                    $features[] = 'Thời hạn ' . $content->class_time . ' ngày';
                }
                if (isset($content->bandwidth) && $content->bandwidth !== '' && $content->bandwidth !== null) {
                    $features[] = 'Lưu lượng ' . $content->bandwidth . ' GB';
                }
                if (isset($content->time) && $content->time !== '' && $content->time !== null) {
                    $features[] = 'Gia hạn ' . $content->time . ' ngày';
                }
                if (isset($content->speed_limit)) {
                    $features[] = ((string) $content->speed_limit === '0')
                        ? 'Tốc độ không giới hạn'
                        : ('Giới hạn tốc độ ' . $content->speed_limit . ' Mbps');
                }
                if (isset($content->ip_limit)) {
                    $features[] = ((string) $content->ip_limit === '0')
                        ? 'Thiết bị đồng thời không giới hạn'
                        : ('Tối đa ' . $content->ip_limit . ' thiết bị đồng thời');
                }

                $features[] = 'Hỗ trợ kỹ thuật qua hệ thống ticket';

                $pricing[] = [
                    'id' => (int) $product->id,
                    'name' => (string) $product->name,
                    'price' => (float) $product->price,
                    'featured' => $count === 1 || $index === 1,
                    'features' => $features,
                ];
                ++$index;
            }
        } catch (Throwable) {
            // Landing must still render if product table/query is unavailable.
            $pricing = [];
        }

        return $response->write(
            $this->view()
                ->assign('pricing_products', $pricing)
                ->assign('has_pricing', $pricing !== [])
                ->assign('landing_year', date('Y'))
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
