<?php

namespace Longman\TelegramBot\Commands\SystemCommands;


use App\Task;
use App\Telegram\Commands\MagicCommand;
use Longman\TelegramBot\Entities\CallbackQuery;

class EditedmessageCommand extends MagicCommand{
    protected $name = 'editedmessage';
    public function execute() {
        $msg = $this->getEditedMessage();
        $conv = $this->getConversation();
        
        if(isset($conv->notes['task_input_id']) && $conv->notes['task_input_id'] == $msg->getMessageId()){
            $conv->notes['task'] = $msg->getText();
            $conv->update();
        }else if($this->getClassId() != null){
            if(($reply_msg = $msg->getReplyToMessage()) !== null){ //TODO: не надёжно мб придётся убрать но ЭТО ОПТИМИЗАЦИЯ!
                Task::where('chat_user_msg_id', $msg->getMessageId())->where('author_id', $msg->getFrom()->getId())->update(['task' => $msg->getText()]); //if fined upd
            }
        }

    }
    
    public function onCallback(CallbackQuery $callbackQuery, array $action, array $edited): array {
        return [];
    }
}
