<?php
namespace Longman\TelegramBot\Commands\UserCommands;


use App\Attachment;
use App\Task;
use App\Telegram\Commands\MagicCommand;
use App\Telegram\Helpers\Week;
use Illuminate\Support\Facades\Storage;
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
				$task = Task::find($task_id);

				switch ($menu){
					case 'task':
						$this->sendMessage([
							'text' => __('tgbot.task.edit_desc'),
//							'reply_markup' => new InlineKeyboard(
//								new InlineKeyboardButton([
//									'text' => __('tgbot.back_button'),
//									'callback_data' => "task_edit_{$task_id}"
//								])
//							)
						]);

						$this->sendMessage([
							'text' => __('tgbot.task.edit_desc_action'),
							'reply_markup' => Keyboard::forceReply()->setSelective(true)
						]);

						$conv = $this->getConversation();
						$conv->setCommand($this);
						$conv->setWaitMsg(true);
						$conv->update();

						return [];
						break;
					case null:
						$edited['text'] = __('tgbot.task.edit_header');
						$edited['reply_markup']= new InlineKeyboard(
							new InlineKeyboardButton([
								'text' => __('tgbot.task.edit_task_btn'),
								"callback_data" => "task_edit_{$task_id}_task"
							]),
							new InlineKeyboardButton([
								'text' => __('tgbot.task.edit_attachments'),
								"callback_data" => "task_edit_{$task_id}_attachment"
							])
						);
						return $edited;
				}


				break;
		}
		return $edited;
	}

	public static function genMsgTask(int $task_id):array {
		$resp = [];
		$task = Task::getById($task_id);
		$task['num']++;

		if($task == null) return  $resp + ['text' => "no data"];
		$resp['text'] = __("tgbot.task.lined", $task + ['date' => Week::humanizeDayAndWeek($task['tweek'], $task['day']), 'weekday' => Week::getDayString($task['day'])]);
		return $resp;
	}
}
