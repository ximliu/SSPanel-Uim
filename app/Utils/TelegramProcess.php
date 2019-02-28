<?php

namespace App\Utils;

use App\Models\User;
use App\Services\Config;
use App\Controllers\LinkController;

class TelegramProcess
{
    private static $all_rss = [
        "clean_link"=>"重置订阅",
        "?sub=2" => "SS订阅" ,
        "?sub=1" => "SSR订阅",
        "?sub=3" => "V2ray订阅",
        "?sub=5" => "Shadowrocket",
        "?sub=4" => "Kitsunebi or v2rayNG",
        "?surge=2" => "Surge 2.x",
        "?surge=3" => "Surge 3.x",
        "?ssd=1" => "SSD",
        "?clash=1" => "Clash",
        "?surfboard=1" => "surfboard",
        "?quantumult=3" => "Quantumult(完整配置)"
        ];

    private static function callback_bind_method($bot,$message,$command){

        $reply_to = $message->getMessageId();
        $user = User::where('telegram_id', $message->getFrom()->getId())->first();
        if ($user != null) {
            switch (true){
                case $command=="?quantumult=3":
                    $ssr_sub_token = LinkController::GenerateSSRSubCode($user->id, 0);
                    $baseUrl =Config::get('baseUrl');
                    $keyboard = new \TelegramBot\Api\Types\Inline\InlineKeyboardMarkup(
                        [
                            [
                                ['text' => '点击跳转', 'url' => $baseUrl."/jump.html?url=quantumult://settings?configuration=clipboard"]
                            ]
                        ]
                    );
                    $bot->sendMessage($user->get_user_attributes("telegram_id"), "两种方法:\n 方法一:\n  1.点击打开以下配置文件\n  2. 选择分享->拷贝到\"Quantumult\"\n  3.选择更新配置\n 方法二:\n  1.长按配置文件\n  2. 选择更多->分享->拷贝\n  3.点击跳转APP,到Quan中保存" , $parseMode = null, $disablePreview = false, $replyToMessageId = null,$replyMarkup=$keyboard);
                    $filepath ='/tmp/tg_'.$ssr_sub_token.'.txt';
                    $fh = fopen($filepath, 'w+');
                    $string = LinkController::GetQuantumult($user,0,3);
                    fwrite($fh, $string);
                    fclose($fh);
                    $bot->sendDocument($user->get_user_attributes("telegram_id"), new \CURLFile($filepath,'','quantumult_'.$ssr_sub_token.'.conf'));
                    unlink($filepath);
                    break;
                case (strpos($command,"sub") or strpos($command,"surge") or strpos($command,"clash") or strpos($command,"surfboard")) :
                    $ssr_sub_token = LinkController::GenerateSSRSubCode($user->id, 0);
                    $subUrl = Config::get('subUrl');
                    $reply_message = self::$all_rss[$command].": ".$subUrl.$ssr_sub_token.$command.PHP_EOL;
                    $bot->sendMessage($message->getChat()->getId(), $reply_message , $parseMode = null, $disablePreview = false, $replyToMessageId = $reply_to);
                    break;
                case ($command=="clean_link"):
                    $user->clean_link();
                    $bot->sendMessage($message->getChat()->getId(), "链接重置成功" , $parseMode = null, $disablePreview = false, $replyToMessageId = $reply_to);
                    break;
                default:
                    $bot->sendMessage($message->getChat()->getId(), "???", $parseMode = null, $disablePreview = false, $replyToMessageId = $reply_to);

            }
        }
    }
    private static function needbind_method($bot, $message, $command, $user, $reply_to = null)
    {
        if ($user != null) {
            switch ($command) {
                case 'traffic':
                    $bot->sendMessage($message->getChat()->getId(), "您当前的流量状况：
今日已使用 " . $user->TodayusedTraffic() . " " . number_format(($user->u + $user->d - $user->last_day_t) / $user->transfer_enable * 100, 2) . "%
今日之前已使用 " . $user->LastusedTraffic() . " " . number_format($user->last_day_t / $user->transfer_enable * 100, 2) . "%
未使用 " . $user->unusedTraffic() . " " . number_format(($user->transfer_enable - ($user->u + $user->d)) / $user->transfer_enable * 100, 2) . "%
					                        ", $parseMode = null, $disablePreview = false, $replyToMessageId = $reply_to);
                    break;
                case 'checkin':
                    if (!$user->isAbleToCheckin()) {
                        $bot->sendMessage($message->getChat()->getId(), "您今天已经签过到了！", $parseMode = null, $disablePreview = false, $replyToMessageId = $reply_to);
                        break;
                    }
                    $traffic = rand(Config::get('checkinMin'), Config::get('checkinMax'));
                    $user->transfer_enable = $user->transfer_enable + Tools::toMB($traffic);
                    $user->last_check_in_time = time();
                    $user->save();
                    $bot->sendMessage($message->getChat()->getId(), "签到成功！获得了 " . $traffic . " MB 流量！", $parseMode = null, $disablePreview = false, $replyToMessageId = $reply_to);
                    break;
                case 'prpr':
                    $prpr = array('⁄(⁄ ⁄•⁄ω⁄•⁄ ⁄)⁄', '(≧ ﹏ ≦)', '(*/ω＼*)', 'ヽ(*。>Д<)o゜', '(つ ﹏ ⊂)', '( >  < )');
                    $bot->sendMessage($message->getChat()->getId(), $prpr[mt_rand(0, 5)], $parseMode = null, $disablePreview = false, $replyToMessageId = $reply_to);
                    break;
                case "rss":
                    $reply_message = "点击以下按钮获取对应订阅: ";
                    $keys = [];
                    foreach (self::$all_rss as $key => $value){
                        $keys[] = [["text" => $value, "callback_data" => $key]];
                    }
                    $keyboard = new \TelegramBot\Api\Types\Inline\InlineKeyboardMarkup(
                        $keys
                    );
                    $bot->sendMessage($message->getChat()->getId(), $reply_message , $parseMode = null, $disablePreview = false, $replyToMessageId = $reply_to,$replyMarkup=$keyboard);
                    break;
                default:

                    $bot->sendMessage($message->getChat()->getId(), "???", $parseMode = null, $disablePreview = false, $replyToMessageId = $reply_to);

            }
        } else {
            $bot->sendMessage($message->getChat()->getId(), "您未绑定本站账号。", $parseMode = null, $disablePreview = false, $replyToMessageId = $reply_to);
        }
    }


    public static function telegram_process($bot, $message, $command)
    {
        $user = User::where('telegram_id', $message->getFrom()->getId())->first();

        if ($message->getChat()->getId() > 0) {
            //个人
            $commands = array("ping", "chat", "traffic", "checkin", "help", "rss");
            if (in_array($command, $commands)) {
                $bot->sendChatAction($message->getChat()->getId(), 'typing');
            }
            switch ($command) {
                case 'ping':
                    $bot->sendMessage($message->getChat()->getId(), 'Pong!这个群组的 ID 是 ' . $message->getChat()->getId() . '!');
                    break;
                case 'chat':
                    $bot->sendMessage($message->getChat()->getId(), Tuling::chat($message->getFrom()->getId(), substr($message->getText(), 5)));
                    break;
                case 'traffic':
                    TelegramProcess::needbind_method($bot, $message, $command, $user);
                    break;
                case 'checkin':
                    TelegramProcess::needbind_method($bot, $message, $command, $user, $message->getMessageId());
                    break;
                case 'prpr':
                    TelegramProcess::needbind_method($bot, $message, $command, $user, $message->getMessageId());
                    break;
                case "rss":
                    TelegramProcess::needbind_method($bot, $message, $command, $user, $message->getMessageId());
                    break;
                case 'help':
                    $help_list = "命令列表：
						/ping  获取群组ID
						/traffic 查询流量
						/checkin 签到
						/help 获取帮助信息
						/rss 获取节点订阅

						您可以在面板里点击 资料编辑 ，滑到页面最下方，就可以看到 Telegram 绑定指示了，绑定您的账号，更多精彩功能等着您去发掘。
					          ";
                    $bot->sendMessage($message->getChat()->getId(), $help_list);
                    break;
                default:
                    if ($message->getPhoto() != null) {
                        $bot->sendMessage($message->getChat()->getId(), "正在解码，请稍候。。。");
                        $bot->sendChatAction($message->getChat()->getId(), 'typing');

                        $photos = $message->getPhoto();

                        $photo_size_array = array();
                        $photo_id_array = array();
                        $photo_id_list_array = array();


                        foreach ($photos as $photo) {
                            $file = $bot->getFile($photo->getFileId());
                            $real_id = substr($file->getFileId(), 0, 36);
                            if (!isset($photo_size_array[$real_id])) {
                                $photo_size_array[$real_id] = 0;
                            }

                            if ($photo_size_array[$real_id] < $file->getFileSize()) {
                                $photo_size_array[$real_id] = $file->getFileSize();
                                $photo_id_array[$real_id] = $file->getFileId();
                                if (!isset($photo_id_list_array[$real_id])) {
                                    $photo_id_list_array[$real_id] = array();
                                }

                                array_push($photo_id_list_array[$real_id], $file->getFileId());
                            }
                        }

                        foreach ($photo_id_array as $key => $value) {
                            $file = $bot->getFile($value);
                            $qrcode_text = QRcode::decode("https://api.telegram.org/file/bot" . Config::get('telegram_token') . "/" . $file->getFilePath());

                            if ($qrcode_text == null) {
                                foreach ($photo_id_list_array[$key] as $fail_key => $fail_value) {
                                    $fail_file = $bot->getFile($fail_value);
                                    $qrcode_text = QRcode::decode("https://api.telegram.org/file/bot" . Config::get('telegram_token') . "/" . $fail_file->getFilePath());
                                    if ($qrcode_text != null) {
                                        break;
                                    }
                                }
                            }

                            if (substr($qrcode_text, 0, 11) == 'mod://bind/' && strlen($qrcode_text) == 27) {
                                $uid = TelegramSessionManager::verify_bind_session(substr($qrcode_text, 11));
                                if ($uid != 0) {
                                    $user = User::where('id', $uid)->first();
                                    $user->telegram_id = $message->getFrom()->getId();
                                    $user->im_type = 4;
                                    $user->im_value = $message->getFrom()->getUsername();
                                    $user->save();
                                    $bot->sendMessage($message->getChat()->getId(), "绑定成功。邮箱：" . $user->email);
                                } else {
                                    $bot->sendMessage($message->getChat()->getId(), "绑定失败，二维码无效。" . substr($qrcode_text, 11));
                                }
                            }

                            if (substr($qrcode_text, 0, 12) == 'mod://login/' && strlen($qrcode_text) == 28) {
                                if ($user != null) {
                                    $uid = TelegramSessionManager::verify_login_session(substr($qrcode_text, 12), $user->id);
                                    if ($uid != 0) {
                                        $bot->sendMessage($message->getChat()->getId(), "登录验证成功。邮箱：" . $user->email);
                                    } else {
                                        $bot->sendMessage($message->getChat()->getId(), "登录验证失败，二维码无效。" . substr($qrcode_text, 12));
                                    }
                                } else {
                                    $bot->sendMessage($message->getChat()->getId(), "登录验证失败，您未绑定本站账号。" . substr($qrcode_text, 12));
                                }
                            }

                            break;
                        }
                    } else {
                        if (is_numeric($message->getText()) && strlen($message->getText()) == 6) {
                            if ($user != null) {
                                $uid = TelegramSessionManager::verify_login_number($message->getText(), $user->id);
                                if ($uid != 0) {
                                    $bot->sendMessage($message->getChat()->getId(), "登录验证成功。邮箱：" . $user->email);
                                } else {
                                    $bot->sendMessage($message->getChat()->getId(), "登录验证失败，数字无效。");
                                }
                            } else {
                                $bot->sendMessage($message->getChat()->getId(), "登录验证失败，您未绑定本站账号。");
                            }
                            break;
                        }
                        $bot->sendMessage($message->getChat()->getId(), Tuling::chat($message->getFrom()->getId(), $message->getText()));
                    }
            }
        } else {
            //群组
            if (Config::get('telegram_group_quiet') == 'true') {
                return;
            }
            $commands = array("ping", "chat", "traffic", "checkin", "help");
            if (in_array($command, $commands)) {
                $bot->sendChatAction($message->getChat()->getId(), 'typing');
            }
            switch ($command) {
                case 'ping':
                    $bot->sendMessage($message->getChat()->getId(), 'Pong!这个群组的 ID 是 ' . $message->getChat()->getId() . '!', $parseMode = null, $disablePreview = false, $replyToMessageId = $message->getMessageId());
                    break;
                case 'chat':
                    if ($message->getChat()->getId() == Config::get('telegram_chatid')) {
                        $bot->sendMessage($message->getChat()->getId(), Tuling::chat($message->getFrom()->getId(), substr($message->getText(), 5)), $parseMode = null, $disablePreview = false, $replyToMessageId = $message->getMessageId());
                    } else {
                        $bot->sendMessage($message->getChat()->getId(), '不约，叔叔我们不约。', $parseMode = null, $disablePreview = false, $replyToMessageId = $message->getMessageId());
                    }
                    break;
                case 'traffic':
                    TelegramProcess::needbind_method($bot, $message, $command, $user, $message->getMessageId());
                    break;
                case 'checkin':
                    TelegramProcess::needbind_method($bot, $message, $command, $user, $message->getMessageId());
                    break;
                case 'prpr':
                    TelegramProcess::needbind_method($bot, $message, $command, $user, $message->getMessageId());
                    break;
                case 'help':
                    $help_list_group = "命令列表：
						/ping  获取群组ID
						/traffic 查询流量
						/checkin 签到
						/help 获取帮助信息
						/rss 获取节点订阅

						您可以在面板里点击 资料编辑 ，滑到页面最下方，就可以看到 Telegram 绑定指示了，绑定您的账号，更多精彩功能等着您去发掘。
					";
                    $bot->sendMessage($message->getChat()->getId(), $help_list_group, $parseMode = null, $disablePreview = false, $replyToMessageId = $message->getMessageId());
                    break;
                default:
                    if ($message->getText() != null) {
                        if ($message->getChat()->getId() == Config::get('telegram_chatid')) {
                            $bot->sendMessage($message->getChat()->getId(), Tuling::chat($message->getFrom()->getId(), $message->getText()), $parseMode = null, $disablePreview = false, $replyToMessageId = $message->getMessageId());
                        } else {
                            $bot->sendMessage($message->getChat()->getId(), '不约，叔叔我们不约。', $parseMode = null, $disablePreview = false, $replyToMessageId = $message->getMessageId());
                        }
                    }
                    if ($message->getNewChatMember() != null && Config::get('enable_welcome_message') == 'true') {
                        $bot->sendMessage($message->getChat()->getId(), "欢迎 " . $message->getNewChatMember()->getFirstName() . " " . $message->getNewChatMember()->getLastName(), $parseMode = null, $disablePreview = false);
                    }
            }
        }

        $bot->sendChatAction($message->getChat()->getId(), '');
    }

    public static function process()
    {
        try {
            $bot = new \TelegramBot\Api\Client(Config::get('telegram_token'));
            // or initialize with botan.io tracker api key
            // $bot = new \TelegramBot\Api\Client('YOUR_BOT_API_TOKEN', 'YOUR_BOTAN_TRACKER_API_KEY');
            $command_list = array("ping", "chat", "traffic", "help", "prpr", "checkin", "rss");
            foreach ($command_list as $command) {
                $bot->command($command, function ($message) use ($bot, $command) {
                    TelegramProcess::telegram_process($bot, $message, $command);
                });
            }

            $bot->on($bot->getEvent(function ($message) use ($bot) {
                TelegramProcess::telegram_process($bot, $message, '');
            }), function () {
                return true;
            });
            $bot->on(function($update) use ($bot){
                $callback = $update->getCallbackQuery();
                //Answer to Telegram, you make answer in the end, or in the beginning.
                $message =  $callback->getMessage();
                $message->setFrom($callback->getFrom());
                TelegramProcess::callback_bind_method($bot,$message,$callback->getData());
            }, function($update){
                $callback = $update->getCallbackQuery();
                if (is_null($callback) || !strlen($callback->getData())){return false; }
                return true;
            });

            $bot->run();
        } catch (\TelegramBot\Api\Exception $e) {
            $e->getMessage();
        }
    }
}
