<?php
namespace Longman\TelegramBot\Commands\SystemCommands;

use App\Telegram\Commands\MagicCommand;
use App\Telegram\Conversation;
use Longman\TelegramBot\Commands\SystemCommand;
use Longman\TelegramBot\Entities\CallbackQuery;

class GenericmessageCommand extends MagicCommand {
	protected $name = 'genericmessage';

	public function execute() {
		$conv = new Conversation(($chat_id = $this->getMessage()->getChat()->getId()), ($user_id = $this->getMessage()->getFrom()->getId()));

		if($conv->isWaitMsg() && ($cmd = $this->getTelegram()->getCommandObject($conv->getCommand())) instanceof MagicCommand){
			/** @var $cmd MagicCommand */
			$cmd->conversation = $conv;
			$cmd->setUpdate($this->getUpdate());

			dump('run onMessage');
			$cmd->onMessage();
		}
	}
	public function onCallback(CallbackQuery $callbackQuery, array $action, array $edited): array {
		return [];
	}

	public function isSystemCommand() {
		return true;
	}
}