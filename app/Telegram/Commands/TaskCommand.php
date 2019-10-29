<?php
namespace Longman\TelegramBot\Commands\UserCommands;


use App\Agenda;
use App\Attachment;
use App\ClassM;
use App\Task;
use App\Telegram\Commands\MagicCommand;
use App\Telegram\Helpers\TaskCropper;
use App\Telegram\Helpers\Week;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use Longman\TelegramBot\DB;
use Longman\TelegramBot\Entities\CallbackQuery;
use Longman\TelegramBot\Entities\InlineKeyboard;
use Longman\TelegramBot\Entities\InlineKeyboardButton;
use Longman\TelegramBot\Entities\Keyboard;
use Longman\TelegramBot\Request;

class TaskCommand extends MagicCommand {
	protected $name = 'task';
	public $private_only = true;

	public function execute() {
		$exp = explode('_', $this->getMessage()->getText(true));

		if ($exp[0] == 'task' && (isset($exp[1]) && is_numeric($task_id = $exp[1]))){
			$this->sendMessage(self::genMsgTask($task_id));

			foreach (Attachment::where('task_id', $task_id)->select('type', 'id', 'file_id','caption')->get() as $attachment){
				$method = 'send'.($type = $attachment->type);
				Request::$method([
					'chat_id' => $this->getMessage()->getChat()->getId(),
					mb_strtolower($type) => $attachment->file_id,
//                    mb_strtolower($type) => $url = Storage::cloud()->temporaryUrl(Attachment::PATH."{$task_id}/{$attachment->id}", now()->addMinutes(5)),
					'caption' => $attachment->caption
				]);
			}
		}
	}

	public function onCallback(CallbackQuery $callbackQuery, array $action, array $edited): array {
		switch ($action[0]){
			case "edit":
				$task_id = (int)$action[1];
				$menu = (isset($action[2]) ? $action[2] : null);
				dump($task_id, $menu);
				/** @var Task $task */
				$task = Task::findOrFail($task_id);
				if($task == null) return [];
				switch ($menu){
					case 'day':
						$day = $task->day;
						$num = $task->num;
						$nowdt = Week::getDtByWeekAndDay($task->week, $task->day);
						$currentWeek = $nowdt->week;

						$lesson_id = Agenda::getScheduleForWeek($this->getClassId(), function ($query)use($day, $num){
							return $query->where([
								['day', '=', $day],
								['num', '=', $num]
							])->limit(1);
						}, $currentWeek, true, false)[0]['lesson_id'];

						$result = Agenda::getScheduleForWeek($this->getClassId(), function ($query)use($currentWeek, $lesson_id){
							return $query->where('agenda.lesson_id', $lesson_id)->whereIn('agenda.week', [$currentWeek, $currentWeek+1, $currentWeek+2, -1]);
						}, null, true, true, false);

						$keyboard = [];
						foreach (NewtaskCommand::collectDBResult($result, $currentWeek) as $week => $lessons){
							foreach ($lessons as $lesson){
								/** @var Carbon $dt */
								$dt = clone $nowdt;
								$dt->week = $week;

								$dt->startOfWeek()->addDays( $lesson['day']-1);

								if($dt->dayOfYear >= $nowdt->dayOfYear){
									$keyboard[] = new InlineKeyboardButton([
										'text' => __('tgbot.task.date_row', ['date' => $dt->format('d.m.Y'), 'weekday' => Week::getDayString($lesson['day']), 'num' => $lesson['num']+1]),
										'callback_data' => "task_edit_{$task_id}_selectDay_{$lesson['num']}_{$lesson['day']}_{$lesson['week']}"
									]);
								}
							}
						}
						$keyboard[] = new InlineKeyboardButton([
							'text' => __('tgbot.back_button'),
							'callback_data' => "task_deletemsg"
						]);

						$resp = $this->sendMessage([
							'text' => __('tgbot.task.get_day_m'),
							'reply_markup' => new InlineKeyboard(...$keyboard)
						]);
//						$conv = $this->getConversation();
//						dump($resp->getResult());
//						$conv->notes['day_ask_msgid'] = $resp->getResult()->message_id;
//						$conv->update();
						return [];
						break;
					case 'selectDay':
						$conv = $this->getConversation();

						if (isset($action[3]) && is_numeric($action[3])){
							$num = $action[3];
							$day = (int)$action[4];
							$week = (int)$action[5];

							$conv->notes['update_task'] = [
								'num' => $num,
								'day' => $day,
								'week' => $week,
							];
							$conv->update();

							$edited['text'] = __('tgbot.task.confirm_move');
							$edited['reply_markup'] = new InlineKeyboard(
								new InlineKeyboardButton([
									'text' => __('tgbot.confirm_yes'),
									'callback_data' => "task_edit_{$task_id}_selectDay_confirm"
								]),
								new InlineKeyboardButton([
									'text' => __('tgbot.back_button'),
									'callback_data' => "task_deletemsg"
								])
							);
							return $edited;
						}elseif($action[3] == "confirm"){
//							if(isset($conv->notes['day_ask_msgid'])){
//								$this->deleteMessage($conv->notes['day_ask_msgid']);
//							}
							$upd = $conv->notes['update_task'];
							dump($upd);
							$task->moveTo($upd['week'], $upd['day'], $upd['num']);
							unset($conv->notes['update_task']);
							$conv->update();

							$this->deleteMessage($this->getMessage()->getMessageId());
							return [];
						}
						return [];
						break;
					case 'task':
						$this->sendMessage([
							'text' => __('tgbot.task.edit_desc'),
						]);

						$this->sendMessage([
							'text' => __('tgbot.task.edit_desc_action'),
							'reply_markup' => Keyboard::forceReply()->setSelective(true)
						]);

						$conv = $this->getConversation();
						$conv->setCommand($this);
						$conv->setWaitMsg(true);
						$conv->notes['task_id'] = $task_id;
						$conv->update();

						return [];
						break;
					case 'delete':
						$user_id = $this->getMessage()->getFrom()->getId();
						if($task->author_id == $user_id || ClassM::find($this->getClassId())->user_owner == $user_id){
							$this->sendMessage([
								'reply_to_message_id' => $this->getMessage()->getMessageId(),
								'text' => __('tgbot.task.err_delete')
							]);

							return [];
						}
						$conv = $this->getConversation();

						if(isset($action[3]) && $action[3] == "confirm"){
							Task::deleteById($task_id);
							if (isset($conv->notes['edit_menu_msgid'])){
								Request::deleteMessage([
									"message_id" => $conv->notes['edit_menu_msgid'],
									"chat_id" => $this->getMessage()->getChat()->getId()
								]);
								unset($conv->notes['edit_menu_msgid']);
								$conv->update();
							}
							return $edited +[
								'text' => __('tgbot.task.deleted'),
								'reply_markup' => $this->getFinalInlineKeyboard($task_id)
							];
						}else{
							$this->sendMessage([
								'text' => __('tgbot.task.delete_confirm'),
								'reply_markup' => new InlineKeyboard(
									new InlineKeyboardButton([
										'text' => __('tgbot.confirm_yes'),
										'callback_data' => "task_edit_{$task_id}_delete_confirm"
									]),
									new InlineKeyboardButton([
										'text' => __('tgbot.back_button'),
										'callback_data' => "task_deletemsg"
									])
								)
							]);
						}


						break;
					case null:
						$conv = $this->getConversation();
						$conv->notes['edit_menu_msgid'] = $this->getCallbackQuery()->getMessage()->getMessageId();
						$conv->update();

						$edited['text'] = __('tgbot.task.edit_header');
						$edited['reply_markup']= new InlineKeyboard(
							new InlineKeyboardButton([
								'text' => __('tgbot.task.edit_task_btn'),
								"callback_data" => "task_edit_{$task_id}_task"
							]),
							new InlineKeyboardButton([
								'text' => __('tgbot.task.edit_attachments'),
								"callback_data" => "task_edit_{$task_id}_attachment"
							]),
							new InlineKeyboardButton([
								'text' => __('tgbot.task.edit_day'),
								"callback_data" => "task_edit_{$task_id}_day"
							]),
							new InlineKeyboardButton([
								'text' => __('tgbot.task.delete_task'),
								"callback_data" => "task_edit_{$task_id}_delete"
							])
						);
						return $edited;
				}
				break;
			case "deletemsg":

				$resp = Request::deleteMessage([
					"chat_id" => $this->getMessage()->getChat()->getId(),
					'message_id' => $this->getMessage()->getMessageId()
				]);
				dump($resp);
				return [];
				break;
		}
		return $edited;
	}

	public function onMessage(): void{
		$text = ($msg = $this->getMessage())->getText();
		if(strlen($text) > 0){
			$conv = $this->getConversation();
			$conv->setWaitMsg(false);
			$task_id = $conv->notes['task_id'];

			Task::editById($task_id, ($cropped = TaskCropper::crop($text))[0], $cropped[1], $msg->getMessageId(), $msg->getFrom()->getId());

			$this->sendMessage([
				'reply_to_message_id' => $this->getMessage()->getMessageId(),
				'text' => __('tgbot.task.accepted'),
				'reply_markup' => $this->getFinalInlineKeyboard($task_id)
			]);

			unset($conv->notes['task_id']);
			$conv->update();
		}
	}

	public static function genMsgTask(int $task_id):array {
		$resp = [];
//		DB::enableQueryLog();
		$task = Task::getById($task_id);
//		dump(DB::getQueryLog());
		$task['num']++;

		if($task == null) return  $resp + ['text' => "no data"];
		$resp['text'] = __("tgbot.task.lined", $task + ['date' => Week::humanizeDayAndWeek($task['tweek'], $task['day']), 'weekday' => Week::getDayString($task['day'])]);
		return $resp;
	}

	public function getFinalInlineKeyboard(int $task_id): InlineKeyboard{
		return new InlineKeyboard(
			new InlineKeyboardButton((isset($this->getConversation()->notes['edit_menu_msgid']) && $this->getMessage()->getChat()->isGroupChat() ? ["url" => "https://t.me/c/".$this->getMessage()->getChat()->getId()."/".$this->getConversation()->notes['edit_menu_msgid']] :
				['callback_data' => "task_edit_{$task_id}"]) + [
				'text' => __('tgbot.back_button'),
			]),
			new InlineKeyboardButton([
				'text' => __('tgbot.back_toMain_button'),
				'callback_data' => "start_hi"
			])
		);
	}
}
