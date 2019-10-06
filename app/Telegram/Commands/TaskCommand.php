<?php
namespace Longman\TelegramBot\Commands\UserCommands;


use App\Task;
use App\Telegram\Commands\MagicCommand;
use App\Telegram\Helpers\Week;
use Longman\TelegramBot\Entities\CallbackQuery;
use Longman\TelegramBot\Request;

class TaskCommand extends MagicCommand {
    protected $name = 'task';
    public $private_only = true;
    
    public function execute() {
        $exp = explode('_', $this->getMessage()->getText(true));
    
        if ($exp[0] == 'task' && (isset($exp[1]) && is_numeric($task_id = $exp[1]))){
            $this->sendMessage(self::genMsgTask($task_id));
            
            $task = Task::getById($task_id);
            $attachments = json_decode($task['attachment_json'], true);
            dump($attachments);
            dump(Request::getFile([
                'file_id' => $attachments[0]
            ]));
            $resp = Request::sendVideo([
                "video" => $attachments[0],
                "chat_id" => $this->getMessage()->getChat()->getId()
            ]);
//            dump($resp);
        }
    }
    
    public function onCallback(CallbackQuery $callbackQuery, array $action, array $edited): array {
    
    }
    
    public static function genMsgTask(int $task_id):array {
        $resp = [];
        $task = Task::getById($task_id);
        $task['num']++;
        
        $resp['text'] = __("tgbot.task.lined", $task + ['date' => Week::humanizeDayAndWeek($task['tweek'], $task['day']), 'weekday' => Week::getDayString($task['day'])]);
        return $resp;
    }
}
