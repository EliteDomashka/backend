<?php
namespace Longman\TelegramBot\Commands\UserCommands;

use App\Agenda;
use App\ClassM;
use App\Telegram\Commands\MagicCommand;
use App\Telegram\Helpers\Week;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\App;
use Longman\TelegramBot\Entities\CallbackQuery;
use Longman\TelegramBot\Entities\InlineKeyboard;
use Longman\TelegramBot\Entities\InlineKeyboardButton;

class SettingsCommand extends MagicCommand {
	public $name = 'settings';
	public $private_only = true;
	
	public function execute() {}

	public function onCallback(CallbackQuery $callbackQuery, array $action, array $edited): array {
		$edited['text'] = 'not implement, '.json_encode($action);
		if($action[0] == 'hi'){
		    $keyboard = [
                new InlineKeyboardButton([
                    'text' => __('tgbot.settings.edit_button'),
                    'callback_data' => 'settings_edit_schedule'
                ]),
                new InlineKeyboardButton([
                    'text' => __('tgbot.settings.language_button'),
                    'callback_data' => 'settings_language_start'
                ])];
		    if($this->getClass()->user_owner == $this->getUser()->id && $this->getClass()->chat_id != $this->getMessage()->getChat()->getId()){
		        $keyboard[] = new InlineKeyboardButton([
                    'text' => __('tgbot.settings.connect_chat_title'),
                    'callback_data' => 'setupclass_bindchat'
                ]);
            }
		    $keyboard[] = new InlineKeyboardButton([
		       'text' => __('tgbot.notify.title'),
               'callback_data' => 'setupclass_notify'
            ]);
		    $keyboard[] = new InlineKeyboardButton([
                'text' => __('tgbot.back_toMain_button'),
                'callback_data' => 'start'
            ]);
		    
			$edited['text'] = __('tgbot.settings.title');
			$edited['reply_markup'] = new InlineKeyboard(...$keyboard);
		}elseif ($action[0] == 'edit'){
			if($action[1] == 'schedule'){
				if(empty($action[2] ?? [])){
					$week = Week::getCurrentWeek();

					$edited['text'] = __('tgbot.settings.schedule_edit');
					$edited['reply_markup'] = new InlineKeyboard(
						new InlineKeyboardButton([
							'text' => __('tgbot.settings.schedule_get_default'),
							'callback_data' => 'settings_edit_schedule_default'
						]),
						new InlineKeyboardButton([
							'text' => __('tgbot.settings.schedule_get_prev', ['week_str' => Week::humanize($week-1)]),
							'callback_data' => 'settings_edit_schedule_prev'
						]),
						new InlineKeyboardButton([
							'text' => __('tgbot.settings.schedule_get_current', ['week_str' => Week::humanize($week)]),
							'callback_data' => 'settings_edit_schedule_current'
						]),
						new InlineKeyboardButton([
							'text' => __('tgbot.settings.schedule_get_next', ['week_str' => Week::humanize($week+1)]),
							'callback_data' => 'settings_edit_schedule_next'
						])
					);
				}else{
					$week = Carbon::now()->weekOfYear;
					switch ($action[2]){
						case 'default':
							$week = -1;
							break;
						case 'prev':
							$week--;
							break;
						case 'next':
							$week++;
							break;
						case 'current':
							//просто так
							break;
					}
					/** @var Collection $data */
					$data = Agenda::getSchedule($this->getClassId())->addSelect('lesson_id')->where('week', $week)->get();
					if($data->count() == 0){
						$callbackQuery->answer([
							'text' => __('tgbot.settings.schedule_err_get', ['data' => __('tgbot.settings.schedule_get_'.$action[2], ['week_str' => Week::humanize($week)])]),
							'show_alert' => true
						]);
						return [];
					}else{
						$day_lessons = [];
						foreach ($data as $row){
							if(!isset($day_lessons[$row['day']])) $day_lessons[$row['day']] = [];
							$day_lessons[$row['day']][$row['num']] = $row['lesson_id'];
						}
						$conv = $this->getConversation();
						$conv->notes['day_lessons'] = $day_lessons;
						$conv->update();

						$edited = $this->getTelegram()->getCommandObject('setup')->onCallback($callbackQuery, ['schedule'], $edited);
					}
				}
			}
		}else if($action[0] == "language"){
			if($action[1] == "start"){
				$edited['text'] = __('tgbot.settings.language_button');

				$keyboard = [];
				foreach ( config('app.locales') as $code => $lang){
					$keyboard[] = new InlineKeyboardButton([
						'text' => ($code == $this->getUser()->lang ? "✅ " : "").$lang,
						'callback_data' => 'settings_language_set_'.$code
					]);
				}

				if($this->getMessage()->getChat()->isPrivateChat()) $keyboard[] = new InlineKeyboardButton([
					'text' => __('tgbot.back_toMain_button'),
					'callback_data' => 'start'
				]);

				$edited['reply_markup'] = new InlineKeyboard(...$keyboard);
			}else if($action[1] == 'set'){
                $edited['text'] = __('tgbot.settings.title');
                if(in_array($lang = $action[2], array_keys(config('app.locales')))){
					App::setLocale($lang);
					$user = $this->getUser();
					$user->lang = $lang;
					$user->save();
					self::$user = $user;

					$callbackQuery->answer(['text' => __('tgbot.callback_answer')]);
				}
				return $this->getTelegram()->getCommandObject('start')->onCallback($callbackQuery, [], $edited);
			}
		}
		return $edited;
	}
}
