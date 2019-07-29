<?php
namespace Longman\TelegramBot\Commands\UserCommands;

use App\Agenda;
use App\Telegram\Commands\MagicCommand;
use Longman\TelegramBot\Entities\CallbackQuery;
use Longman\TelegramBot\Request;

class ScheduleCommand extends MagicCommand{
	protected $name = 'schedule';

	public function execute() {
		Request::sendMessage([
			'chat_id' => $this->getMessage()->getFrom()->getId(),
			'text' => $this->genMsg()
		]);
	}
	public function onCallback(CallbackQuery $callbackQuery, array $action, array $edited): array {
		// TODO: Implement onCallback() method.
	}

	public function genMsg(){
		dump(json_encode($this->getUser()));
		$lo = Agenda::getSchedule($this->getUser()->class_owner)->get();
		return json_encode($lo);
	}
}