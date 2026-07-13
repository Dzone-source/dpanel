<?php

declare(strict_types=1);

namespace App\Services\Bot\Telegram;

use App\Models\Config;
use App\Models\InviteCode;
use App\Models\LoginIp;
use App\Models\OnlineLog;
use App\Models\Payback;
use App\Models\SubscribeLog;
use App\Models\User;
use App\Services\Reward;
use App\Services\Subscribe;
use App\Utils\Tools;
use GuzzleHttp\Exception\GuzzleException;
use Telegram\Bot\Api;
use Telegram\Bot\Exceptions\TelegramSDKException;
use Telegram\Bot\Objects\CallbackQuery;
use function array_chunk;
use function array_merge;
use function end;
use function explode;
use function implode;
use function in_array;
use function is_null;
use function json_encode;
use function time;
use const PHP_EOL;

final class Callback
{
    /**
     * Bot
     */
    private Api $bot;

    /**
     * 触发用户
     */
    private null|User $user;

    /**
     * 触发用户TG信息
     */
    private array $trigger_user;

    /**
     * 回调
     */
    private CallbackQuery $callback;

    /**
     * 回调数据内容
     */
    private ?string $callback_data;

    /**
     * 消息会话 ID
     */
    private int $chat_id;

    /**
     * 触发源信息 ID
     */
    private int $message_id;

    /**
     * 源消息是否可编辑
     */
    private bool $allow_edit_message;

    /**
     * @throws TelegramSDKException|GuzzleException
     */
    public function __construct(Api $bot, CallbackQuery $callback)
    {
        $this->bot = $bot;

        $this->trigger_user = [
            'id' => $callback->from->id,
            'name' => $callback->from->firstName . ' ' . $callback->from->lastName,
            'username' => $callback->from->username,
        ];

        $this->user = Message::getUser($this->trigger_user['id']);
        $this->chat_id = $callback->message->chat->id;
        $this->callback = $callback;
        $this->message_id = $callback->message->messageId;
        $this->callback_data = $callback->data;
        $this->allow_edit_message = time() < $callback->message->date + 172800;

        if ($this->chat_id < 0 && Config::obtain('telegram_group_quiet')) {
            // 群组中不回应
            return;
        }

        if (str_starts_with($this->callback_data, 'user.')) {
            // 用户相关
            $this->userCallback();
        }
    }

    /**
     * 响应回调查询 | 默认已添加 chat_id 和 message_id
     *
     * @param array $send_message
     *
     * @throws TelegramSDKException|GuzzleException
     */
    public function replyWithMessage(array $send_message): void
    {
        $send_message = array_merge(
            [
                'chat_id' => $this->chat_id,
                'message_id' => $this->message_id,
            ],
            $send_message
        );

        if ($this->allow_edit_message) {
            $this->bot->editMessageText($send_message);
        } else {
            $this->bot->sendMessage($send_message);
        }
    }

    /**
     * 响应回调查询 | 默认已添加 callback_query_id
     *
     * <code>
     * [
     *  'text'       => '',
     *  'show_alert' => false
     * ]
     * </code>
     *
     * @param array $send_message
     *
     * @throws TelegramSDKException
     */
    public function answerCallbackQuery(array $send_message): void
    {
        $send_message = array_merge(
            [
                'callback_query_id' => $this->callback->getId(),
                'show_alert' => false,
            ],
            $send_message
        );

        $this->bot->answerCallbackQuery($send_message);
    }

    public static function getUserIndexKeyboard($user): array
    {
        $checkin = (! $user->isAbleToCheckin() ? 'Đã điểm danh' : 'Điểm danh');

        $keyboard = [
            [
                [
                    'text' => 'Trung tâm người dùng',
                    'callback_data' => 'user.center',
                ],
                [
                    'text' => 'Chỉnh sửa hồ sơ',
                    'callback_data' => 'user.edit',
                ],
            ],
            [
                [
                    'text' => 'Trung tâm đăng ký',
                    'callback_data' => 'user.subscribe',
                ],
                [
                    'text' => 'Chương trình giới thiệu',
                    'callback_data' => 'user.invite',
                ],
            ],
            [
                [
                    'text' => $checkin,
                    'callback_data' => 'user.checkin.' . $user->im_value,
                ],
            ],
        ];

        $text = Message::getUserInfo($user);

        return [
            'text' => $text,
            'keyboard' => $keyboard,
        ];
    }

    /**
     * 用户相关回调数据处理
     *
     * @throws TelegramSDKException|GuzzleException
     */
    public function userCallback(): void
    {
        if ($this->user === null && $this->chat_id < 0) {
            // 群组内提示
            $this->answerCallbackQuery([
                'text' => 'Xin chào, bạn chưa liên kết tài khoản, không thể thực hiện thao tác.',
                'show_alert' => true,
            ]);
        }

        $CallbackDataExplode = explode('|', $this->callback_data);
        $Operate = explode('.', $CallbackDataExplode[0]);
        $op_1 = $Operate[1];

        switch ($op_1) {
            case 'edit':
                // 资料编辑
                $this->userEdit();
                break;
            case 'subscribe':
                // 订阅中心
                $this->userSubscribe();
                break;
            case 'invite':
                // 分享计划
                $this->userInvite();
                break;
            case 'checkin':
                // 签到
                if ((int) $Operate[2] !== $this->trigger_user['id']) {
                    $this->answerCallbackQuery([
                        'text' => 'Xin chào, bạn không thể thao tác trên tài khoản của người khác.',
                        'show_alert' => true,
                    ]);
                }
                $this->userCheckin();
                break;
            case 'center':
                // 用户中心
                $this->userCenter();
                break;
            default:
                // 用户首页
                $temp = self::getUserIndexKeyboard($this->user);

                $this->replyWithMessage([
                    'text' => $temp['text'],
                    'parse_mode' => 'HTML',
                    'disable_web_page_preview' => false,
                    'reply_to_message_id' => null,
                    'reply_markup' => json_encode(
                        [
                            'inline_keyboard' => $temp['keyboard'],
                        ]
                    ),
                ]);
        }
    }

    public function getUserCenterKeyboard(): array
    {
        $text = Message::getUserTrafficInfo($this->user);

        $keyboard = [
            [
                [
                    'text' => 'Lịch sử đăng nhập',
                    'callback_data' => 'user.center.login_log',
                ],
                [
                    'text' => 'IP đang trực tuyến',
                    'callback_data' => 'user.center.usage_log',
                ],
            ],
            [
                [
                    'text' => 'Lịch sử hoàn tiền',
                    'callback_data' => 'user.center.rebate_log',
                ],
                [
                    'text' => 'Lịch sử đăng ký',
                    'callback_data' => 'user.center.subscribe_log',
                ],
            ],
            [
                [
                    'text' => 'Về menu chính',
                    'callback_data' => 'user.index',
                ],
            ],
        ];

        return [
            'text' => $text,
            'keyboard' => $keyboard,
        ];
    }

    /**
     * 用户中心
     *
     * @throws TelegramSDKException|GuzzleException
     */
    public function userCenter(): void
    {
        $back = [
            [
                [
                    'text' => 'Về menu chính',
                    'callback_data' => 'user.index',
                ],
                [
                    'text' => 'Quay lại',
                    'callback_data' => 'user.center',
                ],
            ],
        ];

        $CallbackDataExplode = explode('|', $this->callback_data);
        $Operate = explode('.', $CallbackDataExplode[0]);
        $OpEnd = end($Operate);

        switch ($OpEnd) {
            case 'login_log':
                // 登录记录
                $total = (new LoginIp())->where('userid', $this->user->id)
                    ->where('type', '=', 0)
                    ->orderBy('datetime', 'desc')
                    ->take(10)
                    ->get();
                $text = '<strong>Đây là 10 lần đăng nhập gần nhất với IP và vị trí địa lý:</strong>' . PHP_EOL . PHP_EOL;

                foreach ($total as $single) {
                    $text .= $single->ip . ' - ' . Tools::getIpLocation($single->ip) . PHP_EOL;
                }

                $text .= PHP_EOL . '<strong>Lưu ý: Vị trí địa lý được ước tính theo cơ sở dữ liệu MaxMind GeoIP2, có thể không khớp với vị trí thực tế, chỉ mang tính tham khảo</strong>' . PHP_EOL;

                $sendMessage = [
                    'text' => $text,
                    'disable_web_page_preview' => false,
                    'parse_mode' => 'HTML',
                    'reply_to_message_id' => null,
                    'reply_markup' => json_encode(
                        [
                            'inline_keyboard' => $back,
                        ]
                    ),
                ];

                break;
            case 'usage_log':
                // 使用记录
                $logs = (new OnlineLog())->where('user_id', $this->user->id)
                    ->where('last_time', '>', time() - 90)->orderByDesc('last_time')->get('ip');
                $text = '<strong>Đây là IP đang trực tuyến và vị trí địa lý của tài khoản bạn:</strong>' . PHP_EOL . PHP_EOL;

                foreach ($logs as $log) {
                    $ip = $log->ip();
                    $text .= $ip . ' - ' . Tools::getIpLocation($ip) . PHP_EOL;
                }

                $text .= PHP_EOL . '<strong>Lưu ý: Vị trí địa lý được ước tính theo cơ sở dữ liệu MaxMind GeoIP2, có thể không khớp với vị trí thực tế, chỉ mang tính tham khảo</strong>' . PHP_EOL;

                $sendMessage = [
                    'text' => $text,
                    'disable_web_page_preview' => false,
                    'parse_mode' => 'HTML',
                    'reply_to_message_id' => null,
                    'reply_markup' => json_encode(
                        [
                            'inline_keyboard' => $back,
                        ]
                    ),
                ];

                break;
            case 'rebate_log':
                // 返利记录
                $paybacks = (new Payback())->where('ref_by', $this->user->id)->orderBy('datetime', 'desc')->take(10)->get();
                $text = '<strong>Đây là 10 lần hoàn tiền gần nhất:</strong>' . PHP_EOL . PHP_EOL;

                foreach ($paybacks as $payback) {
                    $text .= '<code>#' . $payback->id .
                        '：' . ($payback->user() !== null ? $payback->user()->user_name : 'Đã hủy') . '：' .
                        $payback->ref_get . ' VND</code>' . PHP_EOL;
                }

                $sendMessage = [
                    'text' => $text,
                    'disable_web_page_preview' => false,
                    'parse_mode' => 'HTML',
                    'reply_to_message_id' => null,
                    'reply_markup' => json_encode(
                        [
                            'inline_keyboard' => $back,
                        ]
                    ),
                ];

                break;
            case 'subscribe_log':
                // 订阅记录
                if (Config::obtain('subscribe_log')) {
                    $logs = (new SubscribeLog())->orderBy('id', 'desc')->where('user_id', $this->user->id)->take(10)->get();
                    $text = '<strong>Đây là 10 lần đăng ký gần nhất:</strong>' . PHP_EOL . PHP_EOL;

                    foreach ($logs as $log) {
                        $text .= '<code>' . Tools::toDateTime($log->request_time) .
                            ' truy cập đăng ký ' . $log->type . ' tại [' . $log->request_ip . '] ' . Tools::getIpLocation($log->request_ip) .
                            '</code>' . PHP_EOL;
                    }

                    $text .= PHP_EOL . '<strong>Lưu ý: Vị trí địa lý được ước tính theo cơ sở dữ liệu MaxMind GeoIP2, có thể không khớp với vị trí thực tế, chỉ mang tính tham khảo</strong>' . PHP_EOL;
                } else {
                    $text = 'Trang web chưa bật tính năng ghi lại lịch sử đăng ký';
                }

                $sendMessage = [
                    'text' => $text,
                    'disable_web_page_preview' => false,
                    'parse_mode' => 'HTML',
                    'reply_to_message_id' => null,
                    'reply_markup' => json_encode(
                        [
                            'inline_keyboard' => $back,
                        ]
                    ),
                ];

                break;
            default:
                $temp = $this->getUserCenterKeyboard();

                $sendMessage = [
                    'text' => $temp['text'],
                    'disable_web_page_preview' => false,
                    'reply_to_message_id' => null,
                    'reply_markup' => json_encode(
                        [
                            'inline_keyboard' => $temp['keyboard'],
                        ]
                    ),
                ];

                break;
        }

        $this->replyWithMessage($sendMessage);
    }

    public function getUserEditKeyboard(): array
    {
        $text = 'Bạn có thể chỉnh sửa hồ sơ hoặc thông tin kết nối tại đây:' . PHP_EOL . PHP_EOL;
        $text .= 'Cổng: ' . $this->user->port . PHP_EOL;
        $text .= 'Mật khẩu: ' . $this->user->passwd . PHP_EOL;
        $text .= 'Mã hóa: ' . $this->user->method;

        $keyboard = [
            [
                [
                    'text' => 'Đặt lại liên kết đăng ký',
                    'callback_data' => 'user.edit.update_link',
                ],
                [
                    'text' => 'Đặt lại mật khẩu liên kết',
                    'callback_data' => 'user.edit.update_passwd',
                ],
            ],
            [
                [
                    'text' => 'Thay đổi phương thức mã hóa',
                    'callback_data' => 'user.edit.encrypt',
                ],
                [
                    'text' => 'Hủy liên kết tài khoản',
                    'callback_data' => 'user.edit.unbind',
                ],
            ],
            [
                [
                    'text' => 'Mở khóa nhóm',
                    'callback_data' => 'user.edit.unban',
                ],
            ],
            [
                [
                    'text' => 'Về menu chính',
                    'callback_data' => 'user.index',
                ],
            ],
        ];

        return [
            'text' => $text,
            'keyboard' => $keyboard,
        ];
    }

    /**
     * 用户编辑
     *
     * @throws TelegramSDKException|GuzzleException
     */
    public function userEdit(): void
    {
        if ($this->chat_id < 0) {
            $this->answerCallbackQuery([
                'text' => 'Không thể thực hiện thao tác này trong nhóm.',
                'show_alert' => true,
            ]);
        }

        $back = [
            [
                [
                    'text' => 'Về menu chính',
                    'callback_data' => 'user.index',
                ],
                [
                    'text' => 'Quay lại',
                    'callback_data' => 'user.edit',
                ],
            ],
        ];

        $sendMessage = [];
        $CallbackDataExplode = explode('|', $this->callback_data);
        $Operate = explode('.', $CallbackDataExplode[0]);
        $OpEnd = end($Operate);

        switch ($OpEnd) {
            case 'update_link':
                // 重置订阅链接
                $this->user->removeLink();

                $this->answerCallbackQuery([
                    'text' => 'Đặt lại liên kết đăng ký thành công, vui lòng cập nhật đăng ký bên dưới.',
                    'show_alert' => true,
                ]);

                $temp = $this->getUserSubscribeKeyboard();

                $sendMessage = [
                    'text' => $temp['text'],
                    'disable_web_page_preview' => false,
                    'reply_to_message_id' => null,
                    'reply_markup' => json_encode(
                        [
                            'inline_keyboard' => $temp['keyboard'],
                        ]
                    ),
                ];

                break;
            case 'update_passwd':
                // 重置链接密码
                $this->user->passwd = Tools::genRandomChar();

                if ($this->user->save()) {
                    $answerCallbackQuery = 'Cập nhật mật khẩu kết nối thành công, vui lòng cập nhật đăng ký bên dưới.';
                    $temp = $this->getUserSubscribeKeyboard();
                } else {
                    $answerCallbackQuery = 'Đã xảy ra lỗi, cập nhật mật khẩu kết nối thất bại, vui lòng liên hệ quản trị viên.';
                    $temp = $this->getUserEditKeyboard();
                }

                $this->answerCallbackQuery([
                    'text' => $answerCallbackQuery,
                    'show_alert' => true,
                ]);

                $sendMessage = [
                    'text' => $temp['text'],
                    'disable_web_page_preview' => false,
                    'reply_to_message_id' => null,
                    'reply_markup' => json_encode(
                        [
                            'inline_keyboard' => $temp['keyboard'],
                        ]
                    ),
                ];

                break;
            case 'encrypt':
                // 加密方式更改
                $keyboard = $back;
                $method = Tools::getSsMethod();

                if (isset($CallbackDataExplode[1])) {
                    if (in_array($CallbackDataExplode[1], $method)) {
                        $temp = $this->user->setMethod($CallbackDataExplode[1]);
                        if ($temp['ok']) {
                            $text = 'Phương thức mã hóa hiện tại của bạn: ' . $this->user->method . PHP_EOL . PHP_EOL . $temp['msg'];
                        } else {
                            $text = 'Đã xảy ra lỗi, vui lòng chọn lại.' . PHP_EOL . PHP_EOL . $temp['msg'];
                        }
                    } else {
                        $text = 'Đã xảy ra lỗi, vui lòng chọn lại.';
                    }
                } else {
                    $Encrypts = [];

                    foreach ($method as $value) {
                        $Encrypts[] = [
                            'text' => $value,
                            'callback_data' => 'user.edit.encrypt|' . $value,
                        ];
                    }

                    $Encrypts = array_chunk($Encrypts, 2);
                    $keyboard = [];

                    foreach ($Encrypts as $Encrypt) {
                        $keyboard[] = $Encrypt;
                    }

                    $keyboard[] = $back[0];
                    $text = 'Phương thức mã hóa hiện tại của bạn: ' . $this->user->method;
                }

                $sendMessage = [
                    'text' => $text,
                    'disable_web_page_preview' => false,
                    'reply_to_message_id' => null,
                    'reply_markup' => json_encode(
                        [
                            'inline_keyboard' => $keyboard,
                        ]
                    ),
                ];
                break;
            case 'unbind':
                // Telegram 账户解绑
                $this->allow_edit_message = false;
                $text = 'Gửi **/unbind email_tài_khoản** để hủy liên kết.';
                if (Config::obtain('telegram_unbind_kick_member')) {
                    $text .= PHP_EOL . PHP_EOL . 'Theo cài đặt của quản trị viên, khi hủy liên kết bạn sẽ tự động bị loại khỏi nhóm người dùng.';
                }
                $sendMessage = [
                    'text' => $text,
                    'disable_web_page_preview' => false,
                    'reply_to_message_id' => null,
                    'parse_mode' => 'Markdown',
                    'reply_markup' => null,
                ];
                break;
            case 'unban':
                // 群组解封
                $sendMessage = [
                    'text' => 'Nếu bạn đã ở trong nhóm người dùng, vui lòng không nhấn mở khóa tùy tiện, nếu không bạn sẽ bị loại khỏi nhóm.',
                    'disable_web_page_preview' => false,
                    'reply_to_message_id' => null,
                    'reply_markup' => json_encode(
                        [
                            'inline_keyboard' => [
                                [
                                    [
                                        'text' => 'Nhấn để gửi yêu cầu mở khóa',
                                        'callback_data' => 'user.edit.unban_update',
                                    ],
                                ],
                                $back[0],
                            ],
                        ]
                    ),
                ];

                break;
            case 'unban_update':
                // 提交群组解封
                $this->bot->unbanChatMember(
                    [
                        'chat_id' => Config::obtain('telegram_chatid'),
                        'user_id' => $this->trigger_user['id'],
                    ]
                );

                $this->answerCallbackQuery([
                    'text' => 'Đã gửi yêu cầu mở khóa, nếu bạn vẫn không thể tham gia nhóm, vui lòng liên hệ quản trị viên.',
                    'show_alert' => true,
                ]);

                break;
            default:
                $temp = $this->getUserEditKeyboard();

                $sendMessage = [
                    'text' => $temp['text'],
                    'disable_web_page_preview' => false,
                    'reply_to_message_id' => null,
                    'reply_markup' => json_encode(
                        [
                            'inline_keyboard' => $temp['keyboard'],
                        ]
                    ),
                ];

                break;
        }

        if (! isset($sendMessage['parse_mode'])) {
            $sendMessage['parse_mode'] = 'HTML';
        }

        $this->replyWithMessage($sendMessage);
    }

    public function getUserSubscribeKeyboard(): array
    {
        $text = 'Chọn loại liên kết đăng ký bạn muốn sử dụng:';

        $keyboard = [
            [
                [
                    'text' => 'Clash',
                    'callback_data' => 'user.subscribe|clash',
                ],
                [
                    'text' => 'Json',
                    'callback_data' => 'user.subscribe|json',
                ],
                [
                    'text' => 'SIP008',
                    'callback_data' => 'user.subscribe|sip008',
                ],
            ],
            [
                [
                    'text' => 'SingBox',
                    'callback_data' => 'user.subscribe|singbox',
                ],
                [
                    'text' => 'V2RayJson',
                    'callback_data' => 'user.subscribe|v2rayjson',
                ],
                [
                    'text' => 'Shadowsocks',
                    'callback_data' => 'user.subscribe|ss',
                ],
            ],
            [
                [
                    'text' => 'SIP002',
                    'callback_data' => 'user.subscribe|sip002',
                ],
                [
                    'text' => 'V2Ray',
                    'callback_data' => 'user.subscribe|v2',
                ],
                [
                    'text' => 'Trojan',
                    'callback_data' => 'user.subscribe|trojan',
                ],
            ],
            [
                [
                    'text' => 'Về menu chính',
                    'callback_data' => 'user.index',
                ],
            ],
        ];

        if (! Config::obtain('enable_ss_sub')) {
            unset($keyboard[0][2]);
            unset($keyboard[1][1]);
            unset($keyboard[1][2]);
        }

        if (! Config::obtain('enable_v2_sub')) {
            unset($keyboard[2][0]);
        }

        if (! Config::obtain('enable_trojan_sub')) {
            unset($keyboard[2][1]);
        }

        return [
            'text' => $text,
            'keyboard' => array_values($keyboard),
        ];
    }

    /**
     * 用户订阅
     *
     * @throws TelegramSDKException|GuzzleException
     */
    public function userSubscribe(): void
    {
        $CallbackDataExplode = explode('|', $this->callback_data);
        // 订阅中心
        if (isset($CallbackDataExplode[1])) {
            $temp = [];

            $temp['keyboard'] = [
                [
                    [
                        'text' => 'Về menu chính',
                        'callback_data' => 'user.index',
                    ],
                    [
                        'text' => 'Quay lại',
                        'callback_data' => 'user.subscribe',
                    ],
                ],
            ];

            $UniversalSub_Url = Subscribe::getUniversalSubLink($this->user);

            $text = match ($CallbackDataExplode[1]) {
                'json' => 'Liên kết đăng ký Json chung:' . PHP_EOL . PHP_EOL .
                    '<code>' . $UniversalSub_Url . '/json</code>' . PHP_EOL . PHP_EOL,
                'clash' => 'Liên kết đăng ký Clash chung:' . PHP_EOL . PHP_EOL .
                    '<code>' . $UniversalSub_Url . '/clash</code>' . PHP_EOL . PHP_EOL,
                'singbox' => 'Liên kết đăng ký SingBox chung:' . PHP_EOL . PHP_EOL .
                    '<code>' . $UniversalSub_Url . '/singbox</code>' . PHP_EOL . PHP_EOL,
                'v2rayjson' => 'Liên kết đăng ký V2RayJson chung:' . PHP_EOL . PHP_EOL .
                    '<code>' . $UniversalSub_Url . '/v2rayjson</code>' . PHP_EOL . PHP_EOL,
                'sip008' => 'Liên kết đăng ký SIP008 chung:' . PHP_EOL . PHP_EOL .
                    '<code>' . $UniversalSub_Url . '/sip008</code>' . PHP_EOL . PHP_EOL,
                'ss' => 'Liên kết đăng ký client Shadowsocks:' . PHP_EOL . PHP_EOL .
                    '<code>' . $UniversalSub_Url . '/ss</code>' . PHP_EOL . PHP_EOL,
                'sip002' => 'Liên kết đăng ký client SIP002:' . PHP_EOL . PHP_EOL .
                    '<code>' . $UniversalSub_Url . '/sip002</code>' . PHP_EOL . PHP_EOL,
                'v2' => 'Liên kết đăng ký client V2Ray:' . PHP_EOL . PHP_EOL .
                    '<code>' . $UniversalSub_Url . '/v2ray</code>' . PHP_EOL . PHP_EOL,
                'trojan' => 'Liên kết đăng ký client Trojan:' . PHP_EOL . PHP_EOL .
                    '<code>' . $UniversalSub_Url . '/trojan</code>' . PHP_EOL . PHP_EOL,
                default => 'Tham số không xác định' . PHP_EOL . PHP_EOL,
            };

            $sendMessage = [
                'text' => $text,
                'disable_web_page_preview' => true,
                'reply_to_message_id' => null,
                'reply_markup' => json_encode(
                    [
                        'inline_keyboard' => $temp['keyboard'],
                    ]
                ),
            ];
        } else {
            $temp = $this->getUserSubscribeKeyboard();

            $sendMessage = [
                'text' => $temp['text'],
                'disable_web_page_preview' => false,
                'reply_to_message_id' => null,
                'reply_markup' => json_encode(
                    [
                        'inline_keyboard' => $temp['keyboard'],
                    ]
                ),
            ];
        }

        $this->replyWithMessage(
            array_merge(
                [
                    'parse_mode' => 'HTML',
                ],
                $sendMessage
            )
        );
    }

    public function getUserInviteKeyboard(): array
    {
        $paybacks_sum = (new Payback())->where('ref_by', $this->user->id)->sum('ref_get');

        if (is_null($paybacks_sum)) {
            $paybacks_sum = 0;
        }

        $text = [
            '<strong>Mỗi khi bạn mời <code>1</code> người dùng đăng ký:</strong>',
            '',
            '- Bạn sẽ nhận được <code>' . Config::obtain('invite_reg_traffic_reward') . 'G</code> lưu lượng thưởng.',
            '- Người được mời sẽ nhận <code>' . Config::obtain('invite_reg_money_reward') . ' VND</code> số dư tài khoản ban đầu.',
            '- Khi người được mời thanh toán hóa đơn, bạn sẽ nhận <code>' . Config::obtain('invite_reward_rate') * 100 . '%</code> hoàn tiền từ số tiền hóa đơn.',
            '',
            'Hoàn tiền đã nhận: ' . $paybacks_sum . ' VND.',
        ];

        $keyboard = [
            [
                [
                    'text' => 'Lấy liên kết mời của tôi',
                    'callback_data' => 'user.invite.get',
                ],
            ],
            [
                [
                    'text' => 'Về menu chính',
                    'callback_data' => 'user.index',
                ],
            ],
        ];

        return [
            'text' => implode(PHP_EOL, $text),
            'keyboard' => $keyboard,
        ];
    }

    /**
     * 分享计划
     *
     * @throws TelegramSDKException|GuzzleException
     */
    public function userInvite(): void
    {
        $CallbackDataExplode = explode('|', $this->callback_data);
        $Operate = explode('.', $CallbackDataExplode[0]);
        $OpEnd = end($Operate);

        if ($OpEnd === 'get') {
            $this->allow_edit_message = false;
            $code = (new InviteCode())->where('user_id', $this->user->id)->first();

            if ($code === null) {
                $code = (new InviteCode())->add($this->user->id);
            }

            $inviteUrl = $_ENV['baseUrl'] . '/auth/register?code=' . $code->code;
            $text = '<a href="' . $inviteUrl . '">' . $inviteUrl . '</a>';

            $sendMessage = [
                'text' => $text,
                'disable_web_page_preview' => false,
                'reply_to_message_id' => null,
                'reply_markup' => null,
            ];
        } else {
            $temp = $this->getUserInviteKeyboard();

            $sendMessage = [
                'text' => $temp['text'],
                'disable_web_page_preview' => false,
                'reply_to_message_id' => null,
                'reply_markup' => json_encode(
                    [
                        'inline_keyboard' => $temp['keyboard'],
                    ]
                ),
            ];
        }

        $this->replyWithMessage(
            array_merge(
                [
                    'parse_mode' => 'HTML',
                ],
                $sendMessage
            )
        );
    }

    /**
     * 每日签到
     *
     * @throws TelegramSDKException|GuzzleException
     */
    public function userCheckin(): void
    {
        if ($this->user->isAbleToCheckin()) {
            $traffic = Reward::issueCheckinReward($this->user->id);

            if (! $traffic) {
                $msg = 'Điểm danh thất bại';
            } else {
                $msg = 'Đã nhận được ' . $traffic . 'MB lưu lượng';
            }
        } else {
            $msg = 'Bạn đã điểm danh hôm nay rồi';
        }

        $this->answerCallbackQuery([
            'text' => $msg,
            'show_alert' => true,
        ]);
        // 回送信息
        if ($this->chat_id > 0) {
            $temp = self::getUserIndexKeyboard($this->user);
        } else {
            $temp['text'] = Message::getUserTrafficInfo($this->user);

            $temp['keyboard'] = [
                [
                    [
                        'text' => (! $this->user->isAbleToCheckin() ? 'Đã điểm danh' : 'Điểm danh'),
                        'callback_data' => 'user.checkin.' . $this->trigger_user['id'],
                    ],
                ],
            ];
        }

        $this->replyWithMessage([
            'text' => $temp['text'] . PHP_EOL . PHP_EOL . $msg,
            'reply_to_message_id' => $this->message_id,
            'parse_mode' => 'Markdown',
            'reply_markup' => json_encode(
                [
                    'inline_keyboard' => $temp['keyboard'],
                ]
            ),
        ]);
    }
}
