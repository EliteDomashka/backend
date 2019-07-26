<?php
namespace App\Telegram\Commands;

use App\Telegram\Conversation;
use Carbon\Carbon;
use Illuminate\Support\Facades\App;
use Longman\TelegramBot\Commands\UserCommand;
use Longman\TelegramBot\Entities\CallbackQuery;

abstract class MagicCommand extends UserCommand{
	/** @var Conversation */
	public $conversation;

	abstract public function onCallback(CallbackQuery $callbackQuery, array $action, array $edited): array;
	public function onMessage(): void {

	}
	/**
	 * @return Conversation
	 */
	public function getConversation(): Conversation {
		if($this->conversation === null)  $this->conversation = new Conversation(($msg = $this->getMessage())->getChat()->getId(), $msg->getFrom()->getId(), $this->name);
		return $this->conversation;
	}

	public function getMessage() {
		if (($query = $this->getCallbackQuery()) instanceof CallbackQuery){
			return $query->getMessage();
		}
		return parent::getMessage();
	}

	public function preExecute() {
		dump($this->getMessage()->getFrom()->getLanguageCode());
		App::setLocale($this->getMessage()->getFrom()->getLanguageCode());
		return parent::preExecute();
	}
}