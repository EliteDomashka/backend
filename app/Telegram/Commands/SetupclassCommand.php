<?php
namespace Longman\TelegramBot\Commands\UserCommands;

use App\ClassM;
use App\Telegram\Commands\MagicCommand;
use App\User;
use Carbon\Carbon;
use Longman\TelegramBot\Entities\CallbackQuery;
use Longman\TelegramBot\Entities\Chat;
use Longman\TelegramBot\Entities\InlineKeyboard;
use Longman\TelegramBot\Entities\InlineKeyboardButton;
use Longman\TelegramBot\Entities\Keyboard;
use Longman\TelegramBot\Entities\ServerResponse;
use Longman\TelegramBot\Request;

class SetupclassCommand extends MagicCommand {
	public $name = 'setupclass';
	public function execute() {}

	public function onCallback(CallbackQuery $callbackQuery, array $action, array $edited): array {
		$anwser = ['text' => __('tgbot.callback_answer')];

		if ($action[0] == 'start'){
			$edited['text'] = __('tgbot.class.start');
			$edited['reply_markup'] = new InlineKeyboard(
				new InlineKeyboardButton(['text' => __('tgbot.class.confirm_button'), 'callback_data' => 'setupclass_step1'])
			);
		}elseif ($action[0] == 'step1'){
			if(!isset($action[1])){
				$edited['text'] = __('tgbot.class.step1');
				$edited['reply_markup'] = new InlineKeyboard(
					new InlineKeyboardButton(['text' => __('tgbot.class.school_button'), 'callback_data' => 'setupclass_step2']),
					new InlineKeyboardButton(['text' => __('tgbot.class.another_button'), 'callback_data' => 'setupclass_step1_ERR'])
				);
			}else if($action[1] == "ERR"){
				$edited['text'] = __('tgbot.class.another_desc');
				$edited['reply_markup'] = new InlineKeyboard(
					new InlineKeyboardButton(['text' => __('tgbot.back_button'), 'callback_data' => 'setupclass_step1'])
				);
			}
		}elseif ($action[0] == 'step2'){
			$edited['text'] = __('tgbot.class.step2');

			$keyboard = [[], []];
			for($i = 5; $i < 12; $i++){
				if($i < 9) $keyboard[0][] = new InlineKeyboardButton(['text' => (string)$i, 'callback_data' => "setupclass_selected_{$i}"]);
				else $keyboard[1][] = new InlineKeyboardButton(['text' => (string)$i, 'callback_data' => "setupclass_selected_{$i}"]);

			}
			$keyboard[] = new InlineKeyboardButton(['text' => __('tgbot.back_button'), 'callback_data' => 'setupclass_step1']);


			$edited['reply_markup'] = new InlineKeyboard(...$keyboard);
		}elseif ($action[0] == 'selected'){
			if (is_numeric($class_num = $action[1])){
				$class = new ClassM();
				$class->class_num = $class_num;
				$class->user_owner = $this->getUser()->id;
				$class->chat_id = $this->getMessage()->getChat()->getId();
				$class->save();



				$conv = $this->getConversation();
				if(isset($conv->notes['finished_query'])){
					/** @var MagicCommand $cmd */
					$cmd = $this->getTelegram()->getCommandObject(($query = $conv->notes['finished_query'])[0]);
					$edited = $cmd->onCallback($callbackQuery, $query[1], $edited);
				}else{
					$edited['text'] = "ERR";
				}
			}
		}elseif ($action[0] == "bindchat"){
		    $conv = $this->getConversation();
		    if (!isset($action[1])){
		        $conv->notes['waitAddToChat'] = $this->getClass()->id;
		        $conv->update();
		        $edited['text'] = __('tgbot.class.bind_chat_instruction');
		        $edited['reply_markup'] = new InlineKeyboard(
                    new InlineKeyboardButton([
                        'text' => __('tgbot.back_toMain_button'),
                        'callback_data' => 'setupclass_bindchat_reset'
                    ])
                );
            }else if ($action[1] == 'reset'){
                if(isset($conv->notes['waitAddToChat'])) unset($conv->notes['waitAddToChat']);
                if(isset($conv->notes['notifyWaitAddToChat'])) unset($conv->notes['notifyWaitAddToChat']);
		        $conv->update();
                $callbackQuery->answer($anwser);
            
                return $this->getTelegram()->getCommandObject('start')->onCallback($callbackQuery, [], $edited);
            }
        }elseif ($action[0] == "notify"){
		    
		    if(!isset($action[1])){
		        $class = $this->getClass();
		        $edited['text'] = __('tgbot.notify.title');
		        $edited['reply_markup'] = new InlineKeyboard(
		            $class->notify_time == null ? new InlineKeyboardButton([
		                'text' => __('tgbot.notify.turn_on_daily'),
                        'callback_data' => 'setupclass_notify_turnon'
                    ]) : new InlineKeyboardButton([
                        'text' => __('tgbot.notify.edit_daily'),
                        'callback_data' => 'setupclass_notify_edit'
                    ]),
                    new InlineKeyboardButton([
                        'text' => __('tgbot.back_button'),
                        'callback_data' => 'settings_hi'
                    ])
                );
            }elseif ($action[1] == "turnon"){
                Request::deleteMessage($edited);
                $conv = $this->getConversation();
                $conv->setCommand($this);
                $conv->setWaitMsg(true);
                $conv->notes['isedit'] = true;
                $conv->update();
            
                $edited['text'] = __('tgbot.notify.get_time');
		        
		        dump(date_default_timezone_get());
		        $dt = Carbon::now();
		        
		        $keyboard = [];
		        $keyFlag = 0;
		        for ($hour = 9; $hour <= 22; $hour++){
		            $row = isset($keyboard[$keyFlag]) ? $keyboard[$keyFlag] : [];
		            foreach ([0, 30] as $minute){
		                $dt->setTime($hour, $minute);
		                $row[] = $dt->format('H:i');
                    }
                    $keyboard[$keyFlag] = $row;
		            if(count($row) > 3) $keyFlag++;
                }
		        
		        $edited['reply_markup'] = (new Keyboard(...$keyboard))->setOneTimeKeyboard(true)->setSelective(true);
		        $this->sendMessage($edited);
		        return  [];
            }elseif ($action[1] == "edit"){
		        $edited['text'] = __('tgbot.notify.edit_daily');
		        $edited['reply_markup'] = new InlineKeyboard(
		            new InlineKeyboardButton([
		                'text' => __('tgbot.notify.upd_time'),
                        'callback_data' => "setupclass_notify_turnon_edit"
                    ]),
                    new InlineKeyboardButton([
                        'text' => __('tgbot.notify.upd_chat'),
                        'callback_data' => 'setupclass_notify_chatupd'
                    ])
                    
                );
            }elseif($action[1] == "chatupd"){
		        return $edited + $this->getChatGetMsg();
            }elseif ($action[1] == "selectchat"){
		        $edited['text'] = __('tgbot.class.bind_chat_instruction');
		        
                $conv = $this->getConversation();
                $conv->notes['notifyWaitAddToChat'] = $this->getClass()->id;
                $conv->update();
                
                $edited['reply_markup'] = new InlineKeyboard(
                    new InlineKeyboardButton([
                        'text' => __('tgbot.back_toMain_button'),
                        'callback_data' => 'setupclass_bindchat_reset'  //да да, так и задмуивалось
                    ])
                );
            }elseif ($action[1] == "complete"){
		        $class = $this->getClass();
		        
		        if($class->notify_time !== null){
		            if(isset($action[2]) && $action[2] == "usepared"){
		                $class->notify_chat_id = $class->chat_id;
		                $class->update();
                    }
		            $edited['text'] = __('tgbot.notify.finished');
                    $edited['reply_markup'] = new InlineKeyboard(
                        new InlineKeyboardButton([
                            'text' => __('tgbot.back_toMain_button'),
                            'callback_data' => 'start'
                        ])
                    );
                }else return [];
                
            }
        }

		$callbackQuery->answer($anwser);

		return $edited;
	}
	public function onMessage(): void {
	    $dt = Carbon::createFromFormat('H:i', $text = $this->getMessage()->getText());
        dump($dt);
        
        $class = $this->getClass();
        $class->notify_time = $dt->diffInSeconds(Carbon::now()->startOfDay());
        $class->save();
        
        $this->sendMessage([
            'text' => __('tgbot.notify.get_time_ok', ['time'=> $text]),
            'reply_markup' => Keyboard::remove()
        ]);
        
        $conv = $this->getConversation();
        if(isset($conv->notes['isedit'])){
            $this->sendMessage([
                'text' => "ok",
                'reply_markup' => new InlineKeyboard(
                    new InlineKeyboardButton([
                        'text' => __('tgbot.back_button'),
                        'callback_data' => 'setupclass_notify_edit'
                    ])
                )
            ]);
            return;
        }
        
        $this->sendMessage($this->getChatGetMsg());
	}
	protected function getChatGetMsg(): array{
	    $class = $this->getClass();
        return [
            'text' => __('tgbot.notify.get_chat'),
            'reply_markup' => new InlineKeyboard([
                new InlineKeyboardButton([
                    'text' => __('tgbot.notify.this_chat_button'),
                    'callback_data' => 'setupclass_notify_complete'
                ]),
                new InlineKeyboardButton([
                    'text' => __('tgbot.notify.another_chat_button'),
                    'callback_data' => 'setupclass_notify_selectchat'
                ]),
            ], $class->chat_id != null && ($chat = Request::getChat(['chat_id' => $class->chat_id]))->isOk() ? new InlineKeyboardButton([
                'text' => __('tgbot.notify.pared_chat_button', ['chat' => $chat->getResult()->title]),
                'callback_data' => 'setupclass_notify_complete_usepared'
            ]) : null )
        ];
    }
	public static function getInlineKeyboardNotifyComplete(): InlineKeyboard{
	    return new InlineKeyboard(
	      new InlineKeyboardButton([
            'text' => __('tgbot.confirm_yes'),
             'callback_data' => "setupclass_notify_complete"
          ]),
          new InlineKeyboardButton([
              'text' => __('tgbot.back_toMain_button'),
              'callback_data' => 'setupclass_bindchat_reset'  //да да, так и задмуивалось
          ])
        );
    }
}
