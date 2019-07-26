<?php
namespace App\Telegram\Helpers;


use Longman\TelegramBot\Entities\InlineKeyboard;
use Longman\TelegramBot\Entities\InlineKeyboardButton;

use function dump;

class BasicInlineKeyboard extends InlineKeyboard {
	public $data = [];
	public function __construct($data = []) {
		parent::__construct($data);
	}

	public function addButton(InlineKeyboardButton ...$button): BasicInlineKeyboard {
		$this->inline_keyboard[] = $button;

		return $this;
	}
}