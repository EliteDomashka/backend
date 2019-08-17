<?php
namespace Longman\TelegramBot\Commands\UserCommands;

use App\Agenda;
use App\Telegram\Commands\MagicCommand;
use Illuminate\Support\Facades\DB;
use Longman\TelegramBot\Entities\CallbackQuery;
use Longman\TelegramBot\Entities\InlineKeyboard;
use Longman\TelegramBot\Entities\InlineKeyboardButton;
use Longman\TelegramBot\Request;

class NewtaskCommand extends MagicCommand {
	protected $name = 'newtask';
	protected $private_only = true;


	public function execute() {
		dump('newtask');
		$conv = $this->getConversation();
		$conv->setWaitMsg(true);
		$conv->setCommand($this);

		Request::sendMessage([
			'chat_id' => $this->getMessage()->getChat()->getId(),
			'text' => __('tgbot.task.letsgo')
		]);
		$conv->update();
	}
	public function onMessage(): void {
		$conv = $this->getConversation();
		if($conv->isWaitMsg()){
			$conv->notes['task'] = $task = $this->getMessage()->getText();
			Request::sendMessage([
				'chat_id' => $this->getMessage()->getChat()->getId(),
				'text' => __('tgbot.task.taskstr_accepted', ['task' => $task]),
				'reply_markup' => new InlineKeyboard(
					new InlineKeyboardButton([
						'text' => __('tgbot.task.chose_auto'),
						'callback_data' => "newtask_chose_auto"
					]),
					new InlineKeyboardButton([
						'text' => __('tgbot.task.chose_write'),
						'callback_data' => "newtask_chose_write"
					])
				),
				'parse_mode' => 'markdown'
			]);
			$conv->setWaitMsg(false);
			$conv->update();

		}
	}

	public function onCallback(CallbackQuery $callbackQuery, array $action, array $edited): array {
	    if($action[0] == 'chose'){
	        switch ($action[1]){
                case 'auto':
                    $edited['text'] = __('tgbot.task.select_lesson');
                    $keyboard = [];
                    $day = (int)date('N');
                    $currentWeek = (int)date('W');
                    $lessons = Agenda::getScheduleForWeek($this->getUser()->class_owner, function ($query)use($day, $currentWeek){
                        return $query->whereIn('day', [$day, 5])->whereIn("week", [$currentWeek, -1]);
                    }, $currentWeek);

                    if (!isset($lessons[$day])) $day = 5;

                    if (isset($lessons[$day])) foreach ($lessons[$day] as $lesson){
                        $keyboard[] = new InlineKeyboardButton([
                           'text' => $lesson['title'],
                           'callback_data' => "newtask_select_{$lesson['day']}_{$lesson['num']}"
                        ]);
                    }
                    $keyboard[] = new InlineKeyboardButton([
                        'text' => __('tgbot.back_button'),
                        'callback_data' => 'newtask_hi'
                    ]);
                    $edited['reply_markup'] = new InlineKeyboard(...$keyboard);
                    break;
                case 'write':
                    break;
            }
        }elseif ($action[0] == 'select'){
	        dump($action);
	        $edited['text'] = json_encode(Agenda::findNextLesson($this->getUser()->class_owner, $action[1], $action[2]));
        }
        return $edited;
	}
}
