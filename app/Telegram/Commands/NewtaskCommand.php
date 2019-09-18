<?php
namespace Longman\TelegramBot\Commands\UserCommands;

use App\Agenda;
use App\Task;
use App\Telegram\Commands\MagicCommand;
use App\Telegram\Helpers\TaskCropper;
use App\Telegram\Helpers\Week;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Longman\TelegramBot\Entities\CallbackQuery;
use Longman\TelegramBot\Entities\InlineKeyboard;
use Longman\TelegramBot\Entities\InlineKeyboardButton;
use Longman\TelegramBot\Entities\Keyboard;
use Longman\TelegramBot\Request;
use phpDocumentor\Reflection\Types\Null_;

class NewtaskCommand extends MagicCommand {
	protected $name = 'newtask';
	protected $private_only = false;


	public function execute() {
		$conv = $this->getConversation();
		$conv->setWaitMsg(true);
		$conv->setCommand($this);
		$conv->notes['msg_reply_id'] = $this->getMessage()->getMessageId();
        if(isset($conv->notes['wait_lesson'])) unset($conv->notes['wait_lesson']);
        if(isset($notes['waitAttachment'])) unset($notes['waitAttachment']);
        
        $this->sendMessage([
			'text' => __('tgbot.task.letsgo'),
            'reply_markup' => Keyboard::forceReply()->setSelective(true)
		] + ($this->getMessage()->getChat()->isPrivateChat() ? [] : ['reply_to_message_id' => $this->getMessage()->getMessageId()]));
		$conv->update();
	}
	public function onMessage(): void {
		$conv = $this->getConversation();
		dump($conv->notes);
		if(isset($conv->notes['waitAttachment']) && $conv->notes['waitAttachment'] == true){
            $conv->notes['msg_reply_id'] = $this->getMessage()->getMessageId();
            
            $msg = $this->getMessage();
            dump($msg->getDocument() ?? $msg->getPhoto() ?? $msg->getVoice() ?? $msg->getAudio());
            $file = $msg->getDocument() ?? $msg->getPhoto() ?? $msg->getVoice() ?? $msg->getAudio();
            $send = [
                "reply_to_message_id" => $msg->getMessageId(),
                "reply_markup" => new InlineKeyboard(
                    new InlineKeyboardButton([
                        'text' => __('tgbot.back_button'),
                        'callback_data' => 'newtask_step3'
                    ])
                )
            ];
            if($file != null){
                if($file->getFileId() != null){
                    $send['text'] = $file->getFileId();
                    if (!isset($conv->notes['attachments']) || !is_array($conv->notes['attachments'])) $conv->notes['attachments'] = [];
                    $conv->notes['attachments'][] = $file->getFileId();
                    dump($conv->notes);
                }else{
                    $send['text'] = __('tgbot.tasks.wrong_attachment');
                }
            }else{
                $send['text'] = __('tgbot.tasks.need_attachment');
            }
            
            $this->sendMessage($send);
        }else if($this->getMessage()->getChat()->isPrivateChat() || (!$this->getMessage()->getChat()->isPrivateChat() && $this->getMessage()->getReplyToMessage() !== null)){
		    dump('ok');
            $conv->setWaitMsg(false);
            $conv->notes['msg_reply_id'] = $this->getMessage()->getMessageId();

            if(!isset($conv->notes['wait_lesson'])) {
                $conv->notes['task'] = $this->getMessage()->getText();
                $conv->notes['msg_reply_id'] = $conv->notes['task_input_id'] = $this->getMessage()->getMessageId();
                $this->sendMessage($this->genTaskAcceptedMsg());
            }elseif(isset($conv->notes['wait_lesson'])){
                $currentWeek = Week::getCurrentWeek();
                $result = Agenda::getScheduleForWeek($this->getClassId(), function ($query){
                    return $query->where('title', $this->getMessage()->getText());
                }, [$currentWeek, $currentWeek+1], true, true);
                $send = [
                    'reply_to_message_id' => $conv->notes['msg_reply_id']
                ];
                if (!empty($result)){
                    $send['text'] = __('tgbot.task.get_day');

                    $currentWeek = Week::getCurrentWeek();
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
                    $keyboard[] = new InlineKeyboardButton([
                        'text' => __('tgbot.back_button'),
                        'callback_data' => 'newtask_step2'
                    ]);

                    $send['reply_markup'] = (new InlineKeyboard(...$keyboard))->setSelective(false);
                    unset($conv->notes['wait_lesson']);
                }else{
                    $send['text'] = __('tgbot.task.no_lesson', ['lesson' => $this->getMessage()->getText()]);
                    $send['reply_markup'] = $this->genLessonsKeyboard();
                    $conv->setWaitMsg(true);
                    $conv->notes['wait_lesson'] = true;
                }
                $this->sendMessage($send);
            }
			$conv->update();
		}
	}

	public function onCallback(CallbackQuery $callbackQuery, array $action, array $edited): array {
        if($action[0] == 'select' || $action[0] == 'save' || $action[0] == 'step2'){
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
                    $currentWeek = Week::getCurrentWeek();
                    $lessons = Agenda::getScheduleForWeek($this->getClassId(), function ($query)use($day){
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
            $lesson = $conv->notes['lesson'] = Agenda::findNextLesson($this->getClassId(), $action[1], $action[2]);
            $conv->update();
	        dump($lesson);
            $edited = $edited + $this->genMsgAskConfirm();
        }elseif ($action[0] == "selectDay") {
            $conv = $this->getConversation();

            $conv->notes['lesson'] = [
                'day' => $day = $action[1],
                'num' => $num = $action[2],
                'week' => $week = $action[3],
                'timestamp' => ($dt = Week::getDtByWeekAndDay($week, $day))->getTimestamp(),
                'date' => $dt->format('d.m.Y')
            ];
            $conv->update();
            
            $edited = $edited + $this->genMsgAskConfirm();

        }elseif ($action[0] == "attachment"){
	        $conv = $this->getConversation();
        
            $conv->notes['waitAttachment'] = true;
	        $conv->setWaitMsg(true);
	        $conv->update();
	        
	        $edited['text'] = __('tgbot.task.wait_attachment');
	        $edited['reply_markup'] = new InlineKeyboard(
                new InlineKeyboardButton([
                    'text' => __('tgbot.back_button'),
                    'callback_data' => 'newtask_step3'
                ])
            );
        }elseif ($action[0] == "save") {
            $conv = $this->getConversation();
	        $notes = &$conv->notes;
	        
	        Task::add($this->getClassId(), $this->getFrom()->getId(), $notes['task_input_id'], $notes['lesson']['num'], $notes['lesson']['day'], Week::getWeekByTimestamp($notes['lesson']['timestamp']), ($cropped = TaskCropper::crop($notes['task']))[0], $cropped[1], isset($notes['attachments']) ? $notes['attachments'] : []);
	        
            unset($notes['lesson']);
	        unset($notes['task']);
	        unset($notes['task_input_id']);
	        unset($notes['msg_reply_id']);
	        if(isset($notes['waitAttachment'])) unset($notes['waitAttachment']);
	        $conv->update();
	        
            $edited['text'] = __('tgbot.task.saved');
	        $edited['reply_markup'] = new InlineKeyboard(
	            new InlineKeyboardButton([
                    'text' => __('tgbot.goto.tasks'),
                    'callback_data' => 'tasks_show'
                ]),
                $this->getMessage()->getChat()->isPrivateChat() ? new InlineKeyboardButton([
                    'text' => __('tgbot.back_toMain_button'),
                    'callback_data' => 'start'
                ]) : null
            );
        }elseif ($action[0] == "step2"){
	        return $edited + $this->genTaskAcceptedMsg();
        }elseif ($action[0] == 'step3'){
	        return $edited + $this->genMsgAskConfirm();
        }elseif ($action[0] == "hi"){
            Request::deleteMessage($edited);
            $this->execute();
        }
        return $edited;
	}
	private function genLessonsKeyboard(): Keyboard{
        $keyboard = [];
        $co = 0;
        $lessons = Agenda::getScheduleForWeek($this->getClassId(), function ($query){
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
               'text' => __('tgbot.task.add_attachment'),
               'callback_data' => "newtask_attachment"
            ]),
            new InlineKeyboardButton([
                'text' => __('tgbot.back_button'),
                'callback_data' => 'newtask_step2'
            ])
        );
    }
    private function genMsgAskConfirm(){
	    $conv = $this->getConversation();
	    return [
	        'text' =>  __('tgbot.task.confirm', ['task' => $conv->notes['task'], 'date' => $conv->notes['lesson']['date'], 'day' => Week::getDayString($conv->notes['lesson']['day'])]),
            'reply_markup' => $this->getFinishInlineKeyboard()
        ];
    }
	private function genTaskAcceptedMsg(){
	    $keyboard = [
            new InlineKeyboardButton([
                'text' => __('tgbot.task.chose_auto'),
                'callback_data' => "newtask_chose_auto"
            ]),
            new InlineKeyboardButton([
                'text' => __('tgbot.task.chose_write'),
                'callback_data' => "newtask_chose_byLesson"
            ]),
//            $this->getMessage()->getChat()->isPrivateChat() ? new InlineKeyboardButton([
//                'text' => __('tgbot.back_toMain_button'),
//                'callback_data' => 'start'
//            ]) : null
        ];
	    if($this->getMessage()->getChat()->isPrivateChat()){
            $keyboard[] = new InlineKeyboardButton([
                'text' => __('tgbot.back_button'),
                'callback_data' => 'newtask_hi'
            ]);
        }
	    
        return [
            'chat_id' => $this->getMessage()->getChat()->getId(),
            'reply_to_message_id' => $this->getConversation()->notes['msg_reply_id'] ?? null,
            'text' => __('tgbot.task.taskstr_accepted', ['task' => $this->getConversation()->notes['task']]),
            'reply_markup' => new InlineKeyboard(...$keyboard),
            'parse_mode' => 'markdown'
        ];
    }
}
