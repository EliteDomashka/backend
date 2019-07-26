<?php

namespace App\Telegram;

use Illuminate\Support\Facades\Redis;

class Conversation {
    private $chat_id, $user_id, $command;
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

    public function load(){
        $notes = Redis::get($this->getKey());
        if (is_string($notes)){
            $notes = json_decode($notes, true);
            $this->notes = isset($notes[1]) ? $notes[1] : [];
            if ($this->command === null) $this->command = $notes[0];
        }

    }
    public function update(){
        Redis::pSetEx($this->getKey(), 3600000,  json_encode([$this->command, $this->notes]));
    }
    public function stop(){
        Redis::del($this->getKey());
    }
    public function exists(){
        return Redis::exists($this->getKey());
    }
}