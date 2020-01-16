<?php

namespace App\Utils\Telegram\Commands;

use App\Services\Config;
use App\Utils\Telegram\TelegramTools;
use Telegram\Bot\Actions;
use Telegram\Bot\Commands\Command;

/**
 * Class HelpCommand.
 */
class HelpCommand extends Command
{
    /**
     * @var string Command Name
     */
    protected $name = 'help';

    /**
     * @var string Command Description
     */
    protected $description = '[群组/私聊] 系统中可用的所有命令.';

    /**
     * {@inheritdoc}
     */
    public function handle($arguments)
    {
        $Update  = $this->getUpdate();
        $Message = $Update->getMessage();
        if ($Message->getChat()->getId() < 0) {
            if (Config::get('enable_delete_user_cmd') === true) {
                TelegramTools::DeleteMessage([
                    'chatid'      => $Message->getChat()->getId(),
                    'messageid'   => $Message->getMessageId(),
                ]);
            }
            if (Config::get('telegram_group_quiet') === true) {
                return;
            }
        }
        $this->replyWithChatAction(['action' => Actions::TYPING]);
        $commands = $this->telegram->getCommands();
        $text = '系统中可用的所有命令.';
        $text .= PHP_EOL . PHP_EOL;
        foreach ($commands as $name => $handler) {
            $text .= '/' . $name . PHP_EOL . '`    - ' . $handler->getDescription() . '`' . PHP_EOL;
        }
        $response = $this->replyWithMessage(
            [
                'text'                      => $text,
                'parse_mode'                => 'Markdown',
                'disable_web_page_preview'  => false,
                'reply_to_message_id'       => $Message->getMessageId(),
                'reply_markup'              => null,
            ]
        );
        if ($Message->getChat()->getId() < 0) {
            TelegramTools::DeleteMessage([
                'chatid'      => $Message->getChat()->getId(),
                'messageid'   => $response->getMessageId(),
            ]);
        }
    }
}
