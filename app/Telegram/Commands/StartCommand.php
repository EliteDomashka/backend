<?php

namespace Longman\TelegramBot\Commands\SystemCommands;

use App\Telegram\Commands\MagicCommand;
use Longman\TelegramBot\Entities\CallbackQuery;
use Longman\TelegramBot\Entities\InlineKeyboard;
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

		$data = [
			'chat_id' => $chat_id,
			'text'    => __('tgbot.start.hello'),
			'parse_mode' => 'Markdown',
			'reply_markup' => new InlineKeyboard(
				new InlineKeyboardButton([
					'text' => __('tgbot.start.hello_button'),
					'callback_data' => 'start'
				]),
				new InlineKeyboardButton([
					'text' => __('tgbot.start.language_button'),
					'callback_data' => 'settings_language_start'
				])
			)
		];

		return Request::sendMessage($data);
	}
	public function onCallback(CallbackQuery $callbackQuery, array $action, array $edited): array {
		$edited['text'] = __('tgbot.start.step1');
		$edited['reply_markup'] = (new InlineKeyboard(...[
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
