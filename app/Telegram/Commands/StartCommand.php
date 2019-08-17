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
		$data = [
			'chat_id' => $message->getChat()->getId()
		];

		if($this->getUser()->class_owner === null){
			$data = $data + [
				'text'    => __('tgbot.start.hello'),
				'parse_mode' => 'Markdown',
				'reply_markup' => new InlineKeyboard(
					new InlineKeyboardButton([
						'text' => __('tgbot.start.hello_button'),
						'callback_data' => 'start'
					]),
					new InlineKeyboardButton([
						'text' => __('tgbot.settings.language_button'),
						'callback_data' => 'settings_language_start'
					])
				)
			];
		}else{
			$data = $this->genForPro($data);
		}

		return Request::sendMessage($data);
	}
	public function onCallback(CallbackQuery $callbackQuery, array $action, array $edited): array {
		if(empty($action) && $this->getUser()->class_owner == null){
			$edited['text'] = __('tgbot.start.step1');
			$edited['reply_markup'] = (new InlineKeyboard(...[
			new InlineKeyboardButton([
					'text' => __('tgbot.start.step1_button'),
					'callback_data' => 'setup_schedule'
				]),
			]));
		}else{
			$edited = $edited + $this->genForPro($edited);
		}
		return $edited;
	}
	public function genForPro(array $data): array {
		$data['text'] = __('tgbot.start.pro_hi');
		$data['reply_markup'] = new InlineKeyboard(
			new InlineKeyboardButton([
				'text' => __('tgbot.schedule.title'),
				'callback_data' => 'schedule_hi'
			]),
			new InlineKeyboardButton([
				'text' => __('tgbot.task.new'),
				'callback_data' => "newtask_hi"
			]),
			new InlineKeyboardButton([
				'text'=> __('tgbot.settings.title'),
				'callback_data' => 'settings_hi'
			])
		);
		return $data;
	}

	public function isSystemCommand() {
		return true;
	}
}
