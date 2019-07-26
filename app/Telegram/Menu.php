<?php

namespace App\Telegram;


use Longman\TelegramBot\Entities\InlineKeyboard;
use Longman\TelegramBot\Entities\InlineKeyboardButton;

class Menu {

    public static function getMenu(int $menu) {
        $inline_keyboard = null;
        switch ($menu) {
	        case 1:
	        	$inline_keyboard = [
	        	        new InlineKeyboardButton([
	        	        	'text' => __('tgbot.start.hello_button'),
			                'callback_data' => 'start'
		                ]),
			        new InlineKeyboardButton([
				        'text' => __('tgbot.start.language_button'),
				        'callback_data' => 'settings_language_start'
			        ])
		        ];
	        	break;
        }
        return new InlineKeyboard(...$inline_keyboard);
    }
}