<?php


namespace App\Telegram\Helpers;


use Longman\TelegramBot\Entities\InlineKeyboard;

class InlineKeyboardCleaner extends InlineKeyboard {
    public function __construct($data = []) {
        $data = call_user_func_array([$this, 'createFromParams'], func_get_args());
    
        $data['inline_keyboard'] = array_filter($data['inline_keyboard'], function ($val){
            return !empty($val);
        });
    
        parent::__construct($data);
    }
}
