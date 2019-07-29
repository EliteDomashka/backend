<?php
namespace Longman\TelegramBot\Commands\UserCommands;

use App\Telegram\Commands\MagicCommand;
use Illuminate\Support\Facades\App;
use Longman\TelegramBot\Entities\CallbackQuery;
use Longman\TelegramBot\Entities\InlineKeyboard;
use Longman\TelegramBot\Entities\InlineKeyboardButton;

class SettingsCommand extends MagicCommand {
	public $name = 'settings';
	public function execute() {}

	public function onCallback(CallbackQuery $callbackQuery, array $action, array $edited): array {
		if($action[0] == "language"){
			if($action[1] == "start"){
				dump($this->getUser());
				$edited['text'] = __('tgbot.start.language_button');

				$keyboard = [];
				foreach ( config('app.locales') as $code => $lang){
					$keyboard[] = new InlineKeyboardButton([
						'text' => ($code == App::getLocale() ? "✅ " : "").$lang,
						'callback_data' => 'settings_language_set_'.$code
					]);
				}

				$keyboard[] = new InlineKeyboardButton([
					'text' => __('tgbot.back_button'),
					'callback_data' => 'start'
				]);

				$edited['reply_markup'] = new InlineKeyboard(...$keyboard);
			}else if($action[1] == 'set'){
				if(in_array($lang = $action[2], array_keys(config('app.locales')))){
					App::setLocale($lang);
					$user = $this->getUser();
					$user->lang = $lang;
					$user->save();
					$callbackQuery->answer(['text' => __('tgbot.callback_answer')]);
				}
				return $this->getTelegram()->getCommandObject('start')->onCallback($callbackQuery, [], $edited);
			}
		}
		return $edited;
	}
}