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
    public $needclass = true;

	public function execute() {
		Request::sendMessage([
			'chat_id' => $this->getMessage()->getChat()->getId(),
			'text' => $this->genMsg(),
			'parse_mode' => 'Markdown',
			'reply_markup' => new InlineKeyboard(
				new InlineKeyboardButton([
					'text' => __('tgbot.schedule.toggle_full_btn'),
					'callback_data' => 'schedule_full'
				]),
                $this->getMessage()->getChat()->isPrivateChat() ?
                    new InlineKeyboardButton([
                        'text' => __('tgbot.back_toMain_button'),
                        'callback_data' => 'start'
                    ]) : null
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
			$this->getMessage()->getChat()->isPrivateChat() ?
			new InlineKeyboardButton([
				'text' => __('tgbot.back_toMain_button'),
				'callback_data' => 'start'
			]) : null
		);
		return $edited;
	}

	public function genMsg(bool $full = false): string {
		$day = ($dt = Carbon::now())->dayOfWeekIso;
		$currentWeek = Week::getCurrentWeek();
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
        $schedule = Agenda::getScheduleForWeek($this->getClassId(), function ($query)use($getdays){
		    return $query->where(function ($q)use($getdays){
                    $firstDay = array_keys($getdays)[0];
                    foreach ($getdays as $day => $week){
                        $call = ($firstDay == $day ? 'where' : 'orWhere');
                        $q->{$call}(function ($query2)use($day, $week){
                            $query2->whereIn('agenda.week', [$week, -1])->where('agenda.day', $day);
                        });
                    }
                    return $q;
                });
        }, null, false, true, true);

		$str = "";
		foreach ($getdays as $day => $week){
			$str .= "_".Week::getDayString($day)."_ ".(($currentWeek != $week) ? '('.Week::humanizeDayAndWeek($week, $day).')' : "").PHP_EOL;
			if(!isset($schedule[$week])) $week = -1;
			if(isset($schedule[$week][$day])){
				foreach ($schedule[$week][$day] as $row){
					$str .= ($row['num']).". *{$row['title']}*".PHP_EOL;
				}
			}else{
				$str .= __('tgbot.schedule.empty').PHP_EOL;
			}
				$str .= PHP_EOL.PHP_EOL;
		}

		return $str;

	}
}
