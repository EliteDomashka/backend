<?php
namespace App\Telegram\Commands;

use App\ClassM;
use App\Telegram\Conversation;
use App\User;
use Illuminate\Support\Facades\App;
use Longman\TelegramBot\Commands\UserCommand;
use Longman\TelegramBot\Entities\CallbackQuery;
use Longman\TelegramBot\Entities\Message;
use Longman\TelegramBot\Entities\ServerResponse;
use Longman\TelegramBot\Request;

abstract class MagicCommand extends UserCommand{
	/** @var Conversation */
	public $conversation;
	public $needclass = false;

	abstract public function onCallback(CallbackQuery $callbackQuery, array $action, array $edited): array;
	public function onMessage(): void {}

	/**
	 * @return Conversation
	 */
	public function getConversation(): Conversation {
		if($this->conversation === null)  $this->conversation = new Conversation($this->getMessage()->getChat()->getId(), $this->getFrom()->getId(), $this->name);
		return $this->conversation;
	}

	public function getFrom(): \Longman\TelegramBot\Entities\User{
		if (($query = $this->getCallbackQuery()) instanceof CallbackQuery){
			return $query->getFrom();
		}
		return $this->getMessage()->getFrom();
	}
	public function getMessage(): Message{
		if (($query = $this->getCallbackQuery()) instanceof CallbackQuery){
			return $query->getMessage();
		}
		return parent::getMessage() ?? $this->getEditedMessage();
	}
	/** @var ?User */
	static $user = null;
	public function getUser(): User{
		if(self::$user == null){
			$id = $this->getFrom()->getId();
			self::$user = User::firstOrCreate(['id' => $id], ['lang' => $this->getFrom()->getLanguageCode() ?? 'uk']);
			App::setLocale(self::$user->lang);
		}
		return self::$user;
	}
	/** @var ?ClassM */
	static $class = null;
	public function getClass(): ?ClassM{
		if(self::$class == null){
			$id = $this->getMessage()->getChat()->getId();
			self::$class = ClassM::where('chat_id', $id)->orWhere('user_owner',$this->getFrom()->getId())->first();
		}
		return self::$class;
	}
	public function sendMessage(array $data): ServerResponse{
		return Request::sendMessage($data + [
			'chat_id' => $this->getMessage()->getChat()->getId(),
			'parse_mode' => 'markdown'
		]);
	}
	public function getClassId(): ?int{
		return isset($this->getClass()->id) ? $this->getClass()->id : null;
	}
	public function preExecute() {
		dump(json_encode($this->getUser()));
		App::setLocale($this->getUser()->lang);

		if($this->needclass){
			if($this->getClassId() == null){
				return $this->sendMessage(['text' => __('tgbot.error.fail_get_chat'), 'reply_to_message_id' => $this->getMessage()->getMessageId()]);
			}
		}
		if($this->private_only && !($msg = $this->getMessage())->getChat()->isPrivateChat()){
			Request::sendMessage([
				'reply_to_message_id' => $msg->getMessageId(),
				'text'=> __('tgbot.only_private'),
				'parse_mode' => 'markdown',
				'disable_notification' => true
			]);
			return;
		}
		return parent::preExecute();
	}

	public function __toString() {
		return $this->name;
	}
}
