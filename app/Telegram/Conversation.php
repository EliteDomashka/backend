<?php

namespace App\Telegram;

use Illuminate\Support\Facades\Redis;
use pocketmine\utils\BinaryDataException;
use pocketmine\utils\BinaryStream;

class Conversation {
	private $chat_id, $user_id, $command, $waitMsg = false;
	public $notes = [];

	public function __construct($chat_id, $user_id, ?string $command = null) {
		$this->chat_id = $chat_id;
		$this->user_id = $user_id;
		$this->command = $command;

		$this->load();
	}

	private function getKey(): string {
		return (string)($this->chat_id.$this->user_id);
	}

	public function getCommand(): ?string {
		return $this->command;
	}

	public function setCommand(?string $command = null){
		$this->command = $command;
	}

	/*
	 * Возможно у кого-то возникнет вопрос "нах здесь BinaryStream??? чем тебе json не угодил"
	 * Я сам ещё не придмул зачем я так сделал, если в крадце ОПТИМИЗАЦИЯ!
	 * @aquaminer
	 */
	public function load(): void {
		$buffer = Redis::get($this->getKey());
		if (is_string($buffer)){
			try {
				$stream = new BinaryStream($buffer);
				$command = $stream->get($stream->getUnsignedVarLong());
				$this->waitMsg = $stream->getBool();
				$notes = function_exists('igbinary_serialize') ? igbinary_unserialize($stream->getRemaining()) : unserialize($stream->getRemaining());
				$this->notes = $notes ?? [];
				if ($this->command === null) $this->command = $command;
			}catch (BinaryDataException $exp){
				$this->notes = [];
			}
		}

	}

	public function update(): void {
		$stream = new BinaryStream();
		$stream->putUnsignedVarInt(strlen($cmd = $this->command));
		$stream->put($cmd);
		$stream->putBool($this->waitMsg);
		$stream->put(function_exists('igbinary_serialize') ? igbinary_serialize($this->notes) : serialize($this->notes));

		Redis::pSetEx($this->getKey(), 36000000,  $stream->buffer); //TODO: поставить нормлаьний tts, сам не знаб сколько здесь
	}

	public function stop(){
		Redis::del($this->getKey());
	}

	public function exists(){
		return Redis::exists($this->getKey());
	}

	public function isWaitMsg(): bool {
		return $this->waitMsg;
	}

	public function setWaitMsg(bool $waitMsg): void {
		$this->waitMsg = $waitMsg;
	}
}
