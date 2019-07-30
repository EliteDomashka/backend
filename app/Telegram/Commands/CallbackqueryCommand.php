<?php

namespace Longman\TelegramBot\Commands\SystemCommands;


use App\Telegram\Commands\Callback\Callback;
use App\Telegram\Commands\Callback\ReactionCallback;
use App\Telegram\Commands\MagicCommand;
use App\Telegram\Conversation;
use Longman\TelegramBot\Entities\CallbackQuery;
use Longman\TelegramBot\Request;

class CallbackqueryCommand extends MagicCommand {

	protected $name = 'callbackquery';
	protected $description = 'Reply to callback query';
	protected $version = '1.1.1';

	public function execute() {
		$callback_query = $this->getCallbackQuery();
		$callback_data = $callback_query->getData();
		$callback_data = explode('_', $callback_data);
		dump($callback_data);


		$senddata = [
			'chat_id' => $callback_query->getMessage()->getChat()->getId(),
			'message_id' => ($message_id = $callback_query->getMessage()->getMessageId()),
			'parse_mode' => 'markdown',
		];

		if(($cmd = $this->getTelegram()->getCommandObject(array_shift($callback_data))) instanceof MagicCommand){
			/** @var $cmd MagicCommand */
			$cmd->conversation = $this->getConversation();
			$senddata = $cmd->onCallback($callback_query, $callback_data, $senddata);
			dump('MagicCommand end');
		}


		if(!empty($senddata)) {
			$result = Request::editMessageText($senddata);
			if(!$result->isOk()){
				dump($senddata);
				dump($result);
			}
		}else{
			dump('zero');
		}
	}
	public function onCallback(CallbackQuery $callbackQuery, array $action, array $edited): array {
		return [];
	}
	public function isSystemCommand() {
		return true;
	}
}
