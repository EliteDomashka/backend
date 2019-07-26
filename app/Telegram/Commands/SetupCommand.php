<?php
namespace Longman\TelegramBot\Commands\UserCommands;



use App\Lesson;
use App\Telegram\Commands\MagicCommand;
use App\Telegram\Helpers\BasicInlineKeyboard;
use App\Telegram\Helpers\Week;
use Longman\TelegramBot\Entities\CallbackQuery;
use Longman\TelegramBot\Entities\InlineKeyboard;
use Longman\TelegramBot\Entities\InlineKeyboardButton;
use Longman\TelegramBot\Entities\Keyboard;
use Longman\TelegramBot\Request;

use function __;
use function dump;
use function is_array;
use function count;

class SetupCommand extends MagicCommand {
	protected $name = "setup";

	public function execute() {
		return Request::emptyResponse();
	}

	public function onCallback(CallbackQuery $callbackQuery, array $action, array $edited): array {
		dump($action);
		$conv = $this->getConversation();
		$notes = &$conv->notes;
		$conv->setCommand($this->name);

		$anwser = ['callback_query_id' => $callbackQuery->getId(), 'text' => __('tgbot.callback_answer')];

		if($action[0] != 'schedule' && $action[0] != 'weekday'){
			if (!isset($notes['day_lessons']) && !isset($notes['weekday'])){
				$anwser['text'] = __('tgbot.setup.session_fail');
				dump($notes);
				Request::answerCallbackQuery($anwser);
				return [];
			}
		}

		if($action[0] == 'schedule'){
			$edited['text'] = __('tgbot.setup.schedule_hello');
			$keyboard = [];
			foreach (Week::$days as $num => $day){
				$keyboard[] = new InlineKeyboardButton([
					'text' => '['.(($c = count(isset($notes['day_lessons']) && isset($notes['day_lessons'][$num]) ? $notes['day_lessons'][$num] : [])) > 2 ? '✅' : ($c == 0 ? '❌' : '🔘')).'] '.$day,
					'callback_data' => "setup_weekday_{$num}_force"
				]);
			}
			$keyboard[] = new InlineKeyboardButton(['text' => __('tgbot.setup.schedule_save_button'), 'callback_data' => 'setup_saveSchedule']);

			$edited['reply_markup'] = new InlineKeyboard(...$keyboard);
		}elseif ($action[0] == 'weekday'){
			dump($action[1]);
			$conv->setCommand($this->name);

			$notes['weekday'] = $weekday = $action[1];

			if(!isset($notes['day_lessons'])) $notes['day_lessons'] = [];
			if(!isset($notes['day_lessons'][$weekday]))  $notes['day_lessons'][$weekday] = [];
			$c = count($notes['day_lessons'][$weekday]);

			if(isset($action[2]) && $action[2] == "force" && $c > 1){
				$edited['text'] = __('tgbot.setup.schedule_lesson_pos');
				$edited['reply_markup'] = $this->genLessonsGridKeyboard();
			}else{
				$edited['text'] = __('tgbot.setup.schedule_lesson', ['lesson' => $c + 1, 'weekday' => Week::$days[$weekday]]);
				$edited['reply_markup'] = $this->getLessonsKeyboard(isset($notes['page']) ? $notes['page'] : 0);
			}

			Request::deleteMessage($edited);
			Request::sendMessage($edited);
			$edited = [];

			$conv->update();
		}else if($action[0] == "changepos"){
			$lessons = &$conv->notes['day_lessons'][$conv->notes['weekday'] = $action[2]];

			$pos = $action[3];
			if($action[1] == 'up'){
				$newpos = $pos-1;
				if (isset($lessons[$newpos])){
					$temp = $lessons[$newpos];
					$lessons[$newpos] = $lessons[$pos];
					$lessons[$pos] = $temp;
				}else{
					$anwser['show_alert'] = true;
					$anwser['text'] = __('tgbot.setup.schedule_lesson_updpos_fail');
				}
			}else if ($action[1] === 'down'){
				$newpos = $pos+1;
				if (isset($lessons[$newpos])){
					$temp = $lessons[$newpos];
					$lessons[$newpos] = $lessons[$pos];
					$lessons[$pos] = $temp;
				}else{
					$anwser['show_alert'] = true;
					$anwser['text'] = __('tgbot.setup.schedule_lesson_updpos_fail');
				}
			}
			$conv->update();
			Request::answerCallbackQuery($anwser);

			$edited['text'] = __('tgbot.setup.schedule_lesson_pos');
			$edited['reply_markup'] = $this->genLessonsGridKeyboard();
		}else if($action[0] == 'edit'){
			$pos = $action[1];
			$lesson_id = $conv->notes['day_lessons'][$conv->notes['weekday']][$pos];
			$edited['text'] = __('tgbot.setup.schedule_lesson_edit', ['lesson' => Lesson::find($lesson_id)->title]);
			$edited['reply_markup'] = new InlineKeyboard(
				new InlineKeyboardButton(['text' => __('tgbot.setup.remove_button', ['weekday' => Week::$days[$weekday = $conv->notes['weekday']]]), 'callback_data' => "setup_del_{$pos}_".$weekday]),
				new InlineKeyboardButton(['text' => __('tgbot.setup.insert_author_button'), 'callback_data' => "setup_attachauthor_{$lesson_id}"]),
				new InlineKeyboardButton(['text' => __('tgbot.setup.back_button'), 'callback_data' => "setup_weekday_{$weekday}_force"])
			);
		}else if($action[0] == 'del'){
			if($action[2] == "force"){
				$lessons = &$conv->notes['day_lessons'][$conv->notes['weekday']];
				unset($lessons[(int)$action[1]]);
				$lessons = array_values($lessons);
				$conv->update();

				$edited['text'] = __('tgbot.setup.schedule_lesson_pos');
				$edited['reply_markup'] = $this->genLessonsGridKeyboard();

				Request::answerCallbackQuery($anwser);
			}else{
				$edited['text'] = __('tgbot.setup.confirm');
				$edited['reply_markup'] = new InlineKeyboard(
					new InlineKeyboardButton(['text' => __('tgbot.setup.confirm_yes'), 'callback_data' => "setup_{$action[0]}_{$action[1]}_force"]),
					new InlineKeyboardButton(['text' => __('tgbot.setup.confirm_no'), 'callback_data' => 'setup_edit_'.$action[1]])
				);
				dump($edited);
			}
		}
		return $edited;
	}
	public function onMessage(): void {
		$text = $this->getMessage()->getText();
		$conv = $this->getConversation();
		$notes = &$conv->notes;
		if(!isset($notes['day_lessons'])) return;
		
		$prev = ($text == __('tgbot.setup.schedule_lesson_prev') ? true : ($text == __('tgbot.setup.schedule_lesson_next') ? false : null));
		if($prev !== null){
			$page = isset($notes['page']) ? $notes['page'] : 0;
			if($page > 0 & $prev) $page--;
			else $page++;
			
			Request::sendMessage([
				'chat_id' => $this->getMessage()->getChat()->getId(),
				'text' => __( 'tgbot.setup.schedule_lesson', ['lesson' => ($c = count($notes['day_lessons'][$weekday = $notes['weekday']]))+1, 'weekday' => Week::$days[$weekday]]),
				'reply_markup' => $this->getLessonsKeyboard($page),
				'parse_mode' => 'markdown'
			]);

			$notes['page'] = $page;
		}else {
			$lesson = Lesson::firstOrCreate(['title' => $text]);
			$notes['day_lessons'][$notes['weekday']][] = $lesson['id'];

			Request::sendMessage([
				'chat_id' => $this->getMessage()->getChat()->getId(),
				'text' => __('tgbot.setup.schedule_success_lesson', ['weekday' => Week::$days[$notes['weekday']], 'lesson' => $lesson['title'], 'lesson_num' => count($notes['day_lessons'][$notes['weekday']])]),
				'reply_markup' => Keyboard::remove(),
				'parse_mode' => 'markdown',
			]);

			Request::sendMessage([
				'chat_id' => $this->getMessage()->getChat()->getId(),
				'text' => (($c = count($notes['day_lessons'][$notes['weekday']])) > 1) ? __('tgbot.setup.schedule_lesson_pos') : __('tgbot.setup.schedule_lesson_first'),
				'reply_markup' => $this->genLessonsGridKeyboard(),
				'parse_mode' => 'markdown'
			]);
		}

		$conv->update();
	}

	public function genLessonsGridKeyboard(): InlineKeyboard{
		$keyboard = [];
		$notes = $this->getConversation()->notes;

		if(($c = count($data = $notes['day_lessons'][$notes['weekday']])) > 0){
			$titles = Lesson::whereIn('id', array_values($data))->get()->pluck('title', 'id');
			foreach ($notes['day_lessons'][$notes['weekday']] as $pos => $lesson_id){
				$row = [];
				if($c > 1) $row[] = new InlineKeyboardButton(['text' => "⬆️", 'callback_data' => "setup_changepos_up_{$notes['weekday']}_{$pos}"]);
				$row[] = new InlineKeyboardButton(['text' => $titles[$lesson_id], 'callback_data' => 'setup_edit_'.$pos]);
				if ($c > 1) $row[] = new InlineKeyboardButton(['text' => "⬇️", 'callback_data' => "setup_changepos_down_{$notes['weekday']}_{$pos}"]);
				
				$keyboard[] = $row;
			}
		}
		$keyboard[] = [new InlineKeyboardButton(['text' => __('tgbot.setup.add_more_button'), 'callback_data' => 'setup_weekday_'.$notes['weekday']])];
		$keyboard[] = [new InlineKeyboardButton(['text' => __('tgbot.setup.else_day_button'), 'callback_data' => 'setup_schedule'])];

		return new InlineKeyboard(...$keyboard);
	}
	public function getLessonsKeyboard(int $page): Keyboard{
//		$perPage = 15;
		$perPage = 6;
		$count = Lesson::where('verified', true)->count();
		$data = Lesson::where('verified', true)->offset($perPage*$page)->limit($perPage)->get()->pluck('title', 'id');
		$keyboard = [];

		$row = 0;
		foreach ($data as $lesson_title){
			if(!isset($keyboard[$row])) $keyboard[$row] = [];

			$keyboard[$row][] = $lesson_title;

			$c = count($keyboard[$row]);
			if($c == 4) $row++; // дабы следующим шагом следующию строку
		}
		dump($keyboard);

		$row = [];
		if($page != 0) $row[] = __('tgbot.setup.schedule_lesson_prev');
		if ($count > $perPage*($page+1)) $row[] = __('tgbot.setup.schedule_lesson_next');

		$keyboard[] = $row;

		return new Keyboard(...$keyboard);
	}
}