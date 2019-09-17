<?php


namespace Longman\TelegramBot\Commands\SystemCommands;


use App\ClassM;
use App\Telegram\Commands\MagicCommand;
use App\Telegram\Conversation;
use Longman\TelegramBot\Commands\SystemCommand;
use Longman\TelegramBot\Commands\UserCommands\SetupclassCommand;
use Longman\TelegramBot\Entities\CallbackQuery;
use Longman\TelegramBot\Entities\InlineKeyboard;
use Longman\TelegramBot\Entities\InlineKeyboardButton;
use Longman\TelegramBot\Request;

class NewchatmembersCommand extends MagicCommand {
    protected $name = 'newchatmembers';
    /**
     * @var string
     */
    protected $description = 'New Chat Members';

    public function execute() {
        $message = $this->getMessage();
        $chat_id = $message->getChat()->getId();
        
        if ($message->botAddedInChat()) {
            $conv = $this->conversation = new Conversation($user_id = $this->getFrom()->getId(), $user_id);
            
            if(isset($conv->notes['waitAddToChat'])){
                self::$class = $class = ClassM::where('id', $conv->notes['waitAddToChat'])->first();
                $class->chat_id = $chat_id;
                $class->save();
                
                unset($conv->notes['waitAddToChat']);
                $conv->update();
                
                $this->sendMessage([
                    'text' => __('tgbot.class.bind_chat_success', ['chat' => $message->getChat()->getTitle()]),
                    'chat_id'=> $user_id
                ]);
            }elseif(isset($conv->notes['notifyWaitAddToChat'])) {
                /** @var ClassM $class */
                dump($conv->notes['notifyWaitAddToChat']);
                self::$class = $class = ClassM::where('id', $conv->notes['notifyWaitAddToChat'])->first();
                $class->notify_chat_id = $chat_id;
                $class->update();
                
                unset($conv->notes['notifyWaitAddToChat']);
                $conv->update();
                
                if(isset($conv->notes['isedit'])){
                    unset($conv->notes['isedit']);
                    $this->sendMessage([
                        'text' => __('tgbot.notify.chat_select_finish', ['chat' => $message->getChat()->getTitle()]),
                        'reply_markup' => new InlineKeyboard(
                            new InlineKeyboardButton([
                                'text' => __('tgbot.notify.edit_daily'),
                                'callback_data' => 'setupclass_notify_edit'
                            ])
                        ),
                        'chat_id' => $user_id
                    ]);
                }else{
                    $this->sendMessage([
                        'text' => __('tgbot.notify.chat_select_finish', ['chat' => $message->getChat()->getTitle()]),
                        'reply_markup' => SetupclassCommand::getInlineKeyboardNotifyComplete(),
                        'chat_id' => $user_id
                    ]);
                }
            }else{
                    dump($conv->notes);
            }
        }
    }
    
    public function onCallback(CallbackQuery $callbackQuery, array $action, array $edited): array {
        return [];
    }
}
