<?php
namespace Longman\TelegramBot\Commands\UserCommands;

use App\Agenda;
use App\Telegram\Commands\MagicCommand;
use App\Telegram\Helpers\Week;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Longman\TelegramBot\Entities\CallbackQuery;
use Longman\TelegramBot\Entities\InlineKeyboard;
use Longman\TelegramBot\Entities\InlineKeyboardButton;
use Longman\TelegramBot\Request;

class ScheduleCommand extends MagicCommand{
	protected $name = 'schedule';

	public function execute() {
		Request::sendMessage([
			'chat_id' => $this->getMessage()->getChat()->getId(),
			'text' => $this->genMsg(),
			'parse_mode' => 'Markdown',
			'reply_markup' => new InlineKeyboard(
				new InlineKeyboardButton([
					'text' => __('tgbot.schedule.toggle_full_btn'),
					'callback_data' => 'schedule_full'
				])
			)
		]);
	}
	public function onCallback(CallbackQuery $callbackQuery, array $action, array $edited): array {
		$isFull = (isset($action[0]) && $action[0] == "full" ? true : false);
		$edited['text'] = $this->genMsg($isFull);
		$edited['reply_markup'] = new InlineKeyboard(
			!$isFull ?
			new InlineKeyboardButton([
				'text' => __('tgbot.schedule.toggle_full_btn'),
				'callback_data' => 'schedule_full'
			]) : new InlineKeyboardButton([
					'text' => __('tgbot.schedule.toggle_min_btn'),
					'callback_data' => 'schedule'
				]),
			new InlineKeyboardButton([
				'text' => __('tgbot.back_toMain_button'),
				'callback_data' => 'start'
			])
		);
		return $edited;
	}
//	/** @return Carbon */
//	public function getDT(){
//		$dt = Carbon::now();
//		$notes = $this->getConversation()->notes;
//
//		return $dt;
//	}
	public function genMsg(bool $full = false): string {
		$day = ($dt = Carbon::now())->dayOfWeekIso;
		$currentWeek = (int)date('W');
		$getdays = [];

		if(!$full){
			if($day >= 5){
				$getdays[$day] = $currentWeek;
				if($day < 6) $getdays[$day+1] = $currentWeek;
				$getdays[1] = $currentWeek+1;
			}elseif ($day < 5){
				$getdays[$day] = $currentWeek;
				$getdays[$day+1] = $currentWeek;
			}
		} else {
			$week = $day >= 5 ? $currentWeek+1 : $currentWeek;
			for($day = 1; $day <= 6; $day++) {
				$getdays[$day] = $week;
			}
		}
        $schedule = Agenda::getScheduleForWeek($this->getUser()->class_owner, function ($query)use($getdays){
		    return $query->whereIn('day', array_keys($getdays));
        }, array_values($getdays));

		$str = "";
		foreach ($getdays as $day => $week){
			$str .= "_".Week::getDayString($day)."_ ".(($currentWeek != $week) ? '('.Week::humanizeDayAndWeek($week, $day).')' : "").PHP_EOL;
			if(!isset($schedule[$week])) $week = -1;
			if(isset($schedule[$week][$day])){
				foreach ($schedule[$week][$day] as $row){
					$str .= ($row['num']+1).". *{$row['title']}*".PHP_EOL;
				}
			}else{
				$str .= __('tgbot.schedule.empty').PHP_EOL;
			}
				$str .= PHP_EOL.PHP_EOL;
		}

		return $str;

	}
}
