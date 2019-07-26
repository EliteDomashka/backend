<?php
namespace Longman\TelegramBot\Commands\UserCommands;

use App\Telegram\Commands\MagicCommand;
use Longman\TelegramBot\Entities\CallbackQuery;
use Longman\TelegramBot\Entities\InlineKeyboard;
use Longman\TelegramBot\Entities\InlineKeyboardButton;

class SettingsCommand extends MagicCommand {
	public $name = 'settings';
	public function execute() {}

	public function onCallback(CallbackQuery $callbackQuery, array $action, array $edited): array {
		var_dump($action);
		if($action[0] == "language"){
			$edited['text'] = 'not implement';
			$edited['reply_markup'] = new InlineKeyboard(...[
				new InlineKeyboardButton([
					'text' => 'Back',
					'callback_data' => $action[1]
				])
			]);
		}
		return $edited;
	}
}