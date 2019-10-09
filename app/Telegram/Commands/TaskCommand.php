<?php
namespace Longman\TelegramBot\Commands\UserCommands;


use App\Attachment;
use App\Task;
use App\Telegram\Commands\MagicCommand;
use App\Telegram\Helpers\Week;
use Illuminate\Support\Facades\Storage;
use Longman\TelegramBot\Entities\CallbackQuery;
use Longman\TelegramBot\Request;

class TaskCommand extends MagicCommand {
    protected $name = 'task';
    public $private_only = true;
    
    public function execute() {
        $exp = explode('_', $this->getMessage()->getText(true));
    
        if ($exp[0] == 'task' && (isset($exp[1]) && is_numeric($task_id = $exp[1]))){
            $this->sendMessage(self::genMsgTask($task_id));
            
            foreach (Attachment::where('task_id', $task_id)->select('type', 'id', 'caption')->get() as $attachment){
                $method = 'send'.($type = $attachment->type);
                dump($attachment);
                Request::$method([
                    'chat_id' => $this->getMessage()->getChat()->getId(),
                    mb_strtolower($type) => $url = Storage::cloud()->temporaryUrl(Attachment::PATH."{$task_id}/{$attachment->id}", now()->addMinutes(5)),
                    'caption' => $attachment->caption
                ]);
                dump($url);
            }
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
