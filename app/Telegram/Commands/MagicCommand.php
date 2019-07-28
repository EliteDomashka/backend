<?php
namespace App\Telegram\Commands;

use App\Telegram\Conversation;
use App\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\ModelNotFoundException;
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
	/** @var ?User */
	protected $user = null;
	public function getUser(): User{
		if($this->user == null){
			$id = $this->getMessage()->getFrom()->getId();
			dump($this->getMessage()->getFrom()->getLanguageCode());
			$this->user = User::firstOrCreate([
				'id' => $id,
				'lang' => $this->getMessage()->getFrom()->getLanguageCode() ?? 'uk'
			]);
		}
		return $this->user;

	}
	public function preExecute() {
		App::setLocale($this->getUser()->lang);
		return parent::preExecute();
	}
}