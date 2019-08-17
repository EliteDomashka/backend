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
use Longman\TelegramBot\Entities\Keyboard;
use Longman\TelegramBot\Request;

class NewtaskCommand extends MagicCommand {
	protected $name = 'newtask';
	protected $private_only = false;


	public function execute() {
		$conv = $this->getConversation();
		$conv->setWaitMsg(true);
		$conv->setCommand($this);
		$conv->notes['msg_reply_id'] = $this->getMessage()->getMessageId();
        if(isset($conv->notes['wait_lesson'])) unset($conv->notes['wait_lesson']);
        Request::sendMessage([
			'chat_id' => $this->getMessage()->getChat()->getId(),
			'text' => __('tgbot.task.letsgo'),
            'reply_markup' => Keyboard::forceReply()->setSelective(true)
		] + ($this->getMessage()->getChat()->isPrivateChat() ? [] : ['reply_to_message_id' => $this->getMessage()->getMessageId()]));
		$conv->update();
	}
	public function onMessage(): void {
		$conv = $this->getConversation();
		if($conv->isWaitMsg() && ($this->getMessage()->getChat()->isPrivateChat() || !$this->getMessage()->getChat()->isPrivateChat() && $this->getMessage()->getReplyToMessage() !== null)){
            $conv->setWaitMsg(false);
            $conv->notes['msg_reply_id'] = $this->getMessage()->getMessageId();

            if(!isset($conv->notes['wait_lesson'])) {
                $conv->notes['task'] = $this->getMessage()->getText();
                $conv->notes['msg_reply_id'] = $this->getMessage()->getMessageId();
                Request::sendMessage($this->genTaskAcceptedMsg());
            }elseif(isset($conv->notes['wait_lesson'])){
                $currentWeek =(int)date('W');
                $result = Agenda::getScheduleForWeek($this->getUser()->class_owner, function ($query){
                    return $query->where('title', $this->getMessage()->getText());
//                    clearOrdersBy()->select(DB::RAW('DISTINCT ON (lesson_id) lesson_id'), 'title')
                }, [$currentWeek, $currentWeek+1], true, true);
                $send = [
                    'chat_id' => $this->getMessage()->getChat()->getId(),
                    'reply_to_message_id' => $conv->notes['msg_reply_id']
                ];
                if (!empty($result)){
                    $send['text'] = __('tgbot.task.get_day');

                    $currentWeek = (int)date('W');
                    $nowdt = Carbon::now();
                    $data = [];
                    $forWeeks = [-1, $currentWeek, $currentWeek+1];
                    foreach ($forWeeks as $week){
                        if(!isset($data[$week])) $data[$week] = [];
                        if (isset($result[$week])){
                            foreach ($result[$week] as $lesson){
                                $hash = $lesson['day']. $lesson['num'];
                                $lesson['week'] = $week;
                                if(!isset($data[$hash])) $data[$week][$hash] = $lesson;

                            }
                        }elseif(isset($data[$week-1])){ //если $week это $currentWeek+1и его нет, по сути if($currentWeek+1 && !isset($result[$currentWeek+1]))
                            //берйм данные от -1 и дублируем для этой недели
                            $data[$week] = $data[-1];
                            foreach ($data[$week] as &$val){
                                $val['week'] = $week;
                            }
                        }
                    }
                    unset($data[-1]);
                    $keyboard = [];

                    foreach ($data as $week => $lessons){
                        foreach ($lessons as $lesson){
                            /** @var Carbon $dt */
                            $dt = clone $nowdt;
                            $dt->week = $week;
                            $dt->startOfWeek()->addDays( $lesson['day']-1);

                            if($dt->dayOfYear >= $nowdt->dayOfYear){
                                $keyboard[] = new InlineKeyboardButton([
                                   'text' => __('tgbot.task.date_row', ['date' => $dt->format('d.m.Y'), 'weekday' => Week::getDayString($lesson['day']), 'num' => $lesson['num']+1]),
                                    'callback_data' => "newtask_selectDay_{$lesson['day']}_{$lesson['num']}_{$lesson['week']}"
                                ]);
                            }
                        }
                    }

                    $send['reply_markup'] = (new InlineKeyboard(...$keyboard))->setSelective(false);
                    unset($conv->notes['wait_lesson']);
                }else{
                    $send['text'] = __('tgbot.task.no_lesson', ['lesson' => $this->getMessage()->getText()]);
                    $send['reply_markup'] = $this->genLessonsKeyboard();
                    $conv->setWaitMsg(true);
                    $conv->notes['wait_lesson'] = true;
                }
                Request::sendMessage($send);
            }
			$conv->update();
		}
	}

	public function onCallback(CallbackQuery $callbackQuery, array $action, array $edited): array {
        if($action[0] == 'select'){
            $notes = $this->getConversation()->notes;
            if (empty($notes)){
                $callbackQuery->answer(['text' => __('tgbot.setup.session_fail')]);
                return [];
            }
        }
	    if($action[0] == 'chose'){
	        switch ($action[1]){
                case 'auto':
                    $edited['text'] = __('tgbot.task.select_lesson');
                    $keyboard = [];
                    $day = (int)date('N');
                    $currentWeek = (int)date('W');
                    $lessons = Agenda::getScheduleForWeek($this->getUser()->class_owner, function ($query)use($day){
                        return $query->whereIn('day', [$day, 5]);
                    }, $currentWeek);

                    if (!isset($lessons[$day])) $day = 5;

                    if (isset($lessons[$day])) foreach ($lessons[$day] as $lesson){
                        $keyboard[] = new InlineKeyboardButton([
                           'text' => $lesson['title'],
                           'callback_data' => "newtask_selectLesson_{$lesson['day']}_{$lesson['num']}"
                        ]);
                    }
                    $keyboard[] = new InlineKeyboardButton([
                        'text' => __('tgbot.back_button'),
                        'callback_data' => 'newtask_step2'
                    ]);
                    $edited['reply_markup'] = new InlineKeyboard(...$keyboard);
                    break;
                case 'byLesson':
                    Request::deleteMessage($edited);
                    $conv = $this->getConversation();
                    $conv->setWaitMsg(true);
                    $conv->setCommand($this);
                    $conv->notes['wait_lesson'] = true;
                    $conv->update();
//
                    Request::sendMessage([
                        'chat_id' => $this->getMessage()->getChat()->getId(),
                        'text' => __('tgbot.task.select_lesson_hi'),
                        'reply_markup' => $this->genLessonsKeyboard(),
                        'reply_to_message_id' => $conv->notes['msg_reply_id']
                    ]);
                    return [];
                    break;
            }
        }elseif ($action[0] == 'selectLesson'){
	        $conv = $this->getConversation();
            $lesson = $conv->notes['lesson'] = Agenda::findNextLesson($this->getUser()->class_owner, $action[1], $action[2]);
            $conv->update();
	        dump($lesson);
	        $edited['text'] = __('tgbot.task.confirm', ['task' => $conv->notes['task'], 'date' => $lesson['date'], 'day' => Week::getDayString($lesson['day'])]);
	        $edited['reply_markup'] = $this->getFinishInlineKeyboard();
        }elseif ($action[0] == "selectDay") {
            $conv = $this->getConversation();

            $conv->notes['lesson'] = [
                'day' => $day = $action[1],
                'num' => $num = $action[2],
                'week' => $week = $action[3],
                'timestamp' => ($dt = Week::getDtByWeekAndDay($week, $day))->getTimestamp()
            ];
            $edited['text'] = __('tgbot.task.confirm', ['task' => $conv->notes['task'], 'date' => $dt->format('d.m.Y'), 'day' => Week::getDayString($day)]);
            $edited['reply_markup'] = $this->getFinishInlineKeyboard();
            $conv->update();

        }elseif ($action[0] == "save") {
	        $edited['text'] = 'saved (no) '.PHP_EOL.json_encode($this->getConversation()->notes);
        }elseif ($action[0] == "step2"){
	        return $edited + $this->genTaskAcceptedMsg();
        }elseif ($action[0] == "hi"){
            Request::deleteMessage($edited);
            $this->execute();
        }
        return $edited;
	}
	private function genLessonsKeyboard(): Keyboard{
        $keyboard = [];
        $co = 0;
        $lessons = Agenda::getScheduleForWeek($this->getUser()->class_owner, function ($query){
            return $query->clearOrdersBy()->select(DB::RAW('DISTINCT ON (lesson_id) lesson_id'), 'day', 'num', 'title'); // сюда не над добавляить week ЭТО фича
        }, null, true);
        $c = count($lessons);
        for ($i = 0; $i < $c; $i++){
            if($i % 3 == 0) $co++;
            if(!isset($keyboard[$co])) $keyboard[$co] = [];
            $keyboard[$co][] = array_shift($lessons)['title'];
        }

        return (new Keyboard(...$keyboard))->setSelective(true)->setOneTimeKeyboard(true);
    }
    private function getFinishInlineKeyboard(): InlineKeyboard{
	    return new InlineKeyboard(
            new InlineKeyboardButton([
                'text' => __('tgbot.confirm_yes'),
                'callback_data' => 'newtask_save'
            ]),
            new InlineKeyboardButton([
                'text' => __('tgbot.back_button'),
                'callback_data' => 'newtask_step2'
            ])
        );
    }
	private function genTaskAcceptedMsg(){
        return [
            'chat_id' => $this->getMessage()->getChat()->getId(),
            'text' => __('tgbot.task.taskstr_accepted', ['task' => $this->getConversation()->notes['task']]),
            'reply_markup' => new InlineKeyboard(
                new InlineKeyboardButton([
                    'text' => __('tgbot.task.chose_auto'),
                    'callback_data' => "newtask_chose_auto"
                ]),
                new InlineKeyboardButton([
                    'text' => __('tgbot.task.chose_write'),
                    'callback_data' => "newtask_chose_byLesson"
                ]),
                $this->getMessage()->getChat()->isPrivateChat() ? new InlineKeyboardButton([
                    'text' => __('tgbot.back_button'),
                    'callback_data' => 'newtask_hi'
                ]) : null
            ),
            'parse_mode' => 'markdown'
        ];
    }
}
