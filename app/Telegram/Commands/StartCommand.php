<?php

namespace Longman\TelegramBot\Commands\SystemCommands;

use App\Telegram\Commands\MagicCommand;
use App\Telegram\Conversation;
use App\Telegram\Helpers\BasicInlineKeyboard;
use App\Telegram\Menu;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;
use Longman\TelegramBot\Commands\SystemCommand;
use Longman\TelegramBot\Commands\UserCommand;
use Longman\TelegramBot\Entities\CallbackQuery;
use Longman\TelegramBot\Entities\InlineKeyboardButton;
use Longman\TelegramBot\Request;

class StartCommand extends MagicCommand {
	protected $name = 'start';                      // Your command's name
	protected $description = 'Початок'; // Your command description
	protected $usage = '/start';                    // Usage of your command
	protected $version = '0.0.1';                  // Version of your command
	protected $private_only = true;

	public function execute() {
		$message = $this->getMessage();

		$chat_id = $message->getChat()->getId();

		$conversation = new Conversation($chat_id, ($user_id = $message->getFrom()->getId()), 'start');

		$data = [
			'chat_id' => $chat_id,
			'text'    => __('start.hello'),
			'parse_mode' => 'Markdown',
			'reply_markup' => Menu::getMenu(1)
		];

		return Request::sendMessage($data);
	}
	public function onCallback(CallbackQuery $callbackQuery, array $action, array $edited): array {
		$edited['text'] = __('tgbot.start.step1');
		$edited['reply_markup'] = (new BasicInlineKeyboard(...[
			new InlineKeyboardButton([
				'text' => __('tgbot.start.step1_button'),
				'callback_data' => 'setup_schedule'
			]),
		]));
		return $edited;
	}

	public function isSystemCommand() {
		return true;
	}
}
