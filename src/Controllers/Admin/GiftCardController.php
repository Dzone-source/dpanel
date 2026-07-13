<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\GiftCard;
use App\Utils\Tools;
use Exception;
use Psr\Http\Message\ResponseInterface;
use Slim\Http\Response;
use Slim\Http\ServerRequest;
use function time;
use const PHP_EOL;

final class GiftCardController extends BaseController
{
    private static array $details = [
        'field' => [
            'op' => 'Thao tác',
            'id' => 'ID thẻ quà tặng',
            'card' => 'Số thẻ',
            'balance' => 'Mệnh giá',
            'create_time' => 'Thời gian tạo',
            'status' => 'Trạng thái sử dụng',
            'use_time' => 'Thời gian sử dụng',
            'use_user' => 'Người dùng sử dụng',
        ],
        'create_dialog' => [
            [
                'id' => 'card_number',
                'info' => 'Số lượng tạo',
                'type' => 'input',
                'placeholder' => '',
            ],
            [
                'id' => 'card_value',
                'info' => 'Mệnh giá thẻ quà tặng',
                'type' => 'input',
                'placeholder' => '',
            ],
            [
                'id' => 'card_length',
                'info' => 'Độ dài thẻ quà tặng',
                'type' => 'select',
                'select' => [
                    '12' => '12 ký tự',
                    '18' => '18 ký tự',
                    '24' => '24 ký tự',
                    '30' => '30 ký tự',
                    '36' => '36 ký tự',
                ],
            ],
        ],
    ];

    /**
     * @throws Exception
     */
    public function index(ServerRequest $request, Response $response, array $args): ResponseInterface
    {
        return $response->write(
            $this->view()
                ->assign('details', self::$details)
                ->fetch('admin/giftcard.tpl')
        );
    }

    public function add(ServerRequest $request, Response $response, array $args): ResponseInterface
    {
        $card_number = $request->getParam('card_number') ?? 0;
        $card_value = $request->getParam('card_value') ?? 0;
        $card_length = $request->getParam('card_length') ?? 0;
        $card_added = '';

        if ($card_number === '' || $card_number <= 0) {
            return $response->withJson([
                'ret' => 0,
                'msg' => 'Số lượng tạo không được để trống hoặc nhỏ hơn 0',
            ]);
        }

        if ($card_value === '' || $card_value <= 0) {
            return $response->withJson([
                'ret' => 0,
                'msg' => 'Mệnh giá thẻ quà tặng không được để trống hoặc nhỏ hơn 0',
            ]);
        }

        if ($card_length === '' || $card_length <= 0) {
            return $response->withJson([
                'ret' => 0,
                'msg' => 'Độ dài thẻ quà tặng không được để trống hoặc nhỏ hơn 0',
            ]);
        }

        for ($i = 0; $i < $card_number; $i++) {
            $card = Tools::genRandomChar((int) $card_length);
            // save to database
            $giftcard = new GiftCard();
            $giftcard->card = $card;
            $giftcard->balance = $card_value;
            $giftcard->create_time = time();
            $giftcard->status = 0;
            $giftcard->use_time = 0;
            $giftcard->use_user = 0;
            $giftcard->save();
            $card_added .= $card . PHP_EOL;
        }

        return $response->withJson([
            'ret' => 1,
            'msg' => 'Thêm thành công' . PHP_EOL . $card_added,
        ]);
    }

    public function delete(ServerRequest $request, Response $response, array $args): ResponseInterface
    {
        $card_id = $args['id'];
        (new GiftCard())->find($card_id)->delete();

        return $response->withJson([
            'ret' => 1,
            'msg' => 'Xóa thành công',
        ]);
    }

    public function ajax(ServerRequest $request, Response $response, array $args): ResponseInterface
    {
        $giftcards = (new GiftCard())->orderBy('id', 'desc')->get();

        foreach ($giftcards as $giftcard) {
            $giftcard->op = '<button class="btn btn-red" id="delete-gift-card-' . $giftcard->id . '" 
        onclick="deleteGiftCard(' . $giftcard->id . ')">Xóa</button>';
            $giftcard->status = $giftcard->status();
            $giftcard->create_time = Tools::toDateTime((int) $giftcard->create_time);
            $giftcard->use_time = Tools::toDateTime((int) $giftcard->use_time);
        }

        return $response->withJson([
            'giftcards' => $giftcards,
        ]);
    }
}
