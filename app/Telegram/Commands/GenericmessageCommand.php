<?php
namespace Longman\TelegramBot\Commands\SystemCommands;

use App\Task;
use App\Telegram\Commands\MagicCommand;
use App\Telegram\Conversation;
use App\Telegram\Helpers\TaskCropper;
use Longman\TelegramBot\Entities\CallbackQuery;

class GenericmessageCommand extends MagicCommand {
	protected $name = 'genericmessage';

	public function execute() {
		$conv = new Conversation(($chat_id = $this->getMessage()->getChat()->getId()), ($user_id = $this->getFrom()->getId()));

		if($conv->isWaitMsg() && ($cmd = $this->getTelegram()->getCommandObject($conv->getCommand())) instanceof MagicCommand){
			/** @var $cmd MagicCommand */
			$cmd->conversation = $conv;
			$cmd->setUpdate($this->getUpdate());

			dump('run onMessage');
			$cmd->onMessage();
		}elseif (($reply_msg = ($msg = $this->getMessage())->getReplyToMessage()) != null){
			$text = $this->getMessage()->getText();
			if(($symbol = mb_substr($text, 0, 1)) == "*" || $symbol == "*" || $symbol == "*"){
				Task::edit(null, $reply_msg->getMessageId(), $reply_msg->getFrom()->getId(), ...TaskCropper::crop(mb_substr($text, 1))); //TODO: write editor
				$this->sendMessage([
				   'text' => __('tgbot.task.updated'),
				   'reply_to_message_id' => $msg->getMessageId()
				]);
			}

		}
	}
	public function onCallback(CallbackQuery $callbackQuery, array $action, array $edited): array {
		return [];
	}

	public function isSystemCommand() {
		return true;
	}
}
