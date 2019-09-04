<?php


namespace Longman\TelegramBot\Commands\SystemCommands;


use App\ClassM;
use App\Telegram\Commands\MagicCommand;
use App\Telegram\Conversation;
use Longman\TelegramBot\Commands\SystemCommand;
use Longman\TelegramBot\Entities\CallbackQuery;
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
                self:$class = $class = ClassM::where('id', $conv->notes['waitAddToChat'])->first();
                $class->chat_id = $chat_id;
                $class->save();
                
                unset($conv->notes['waitAddToChat']);
                $conv->update();
                
                $this->sendMessage([
                    'text' => __('tgbot.class.bind_chat_success', ['chat' => $message->getChat()->getTitle()]),
                    'chat_id'=> $user_id
                ]);
            }else{
                dump($conv->notes);
            }
        }

    }
    
    public function onCallback(CallbackQuery $callbackQuery, array $action, array $edited): array {
        return [];
    }
}
