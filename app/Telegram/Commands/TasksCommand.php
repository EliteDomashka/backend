<?php
namespace Longman\TelegramBot\Commands\UserCommands;

use App\Task;
use App\Telegram\Commands\MagicCommand;
use App\Telegram\Helpers\Week;
use Carbon\Carbon;
use Longman\TelegramBot\Entities\CallbackQuery;
use Longman\TelegramBot\Request;

class TasksCommand extends MagicCommand {
    protected $name = 'tasks';
    protected $private_only = false;

    public function execute(){
       dump(Request::sendMessage([
          'text' => $this->getTasks(),
          'parse_mode' => 'markdown',
          'chat_id' => $this->getMessage()->getChat()->getId()
       ]));
    }
    public function onCallback(CallbackQuery $callbackQuery, array $action, array $edited): array{
        // TODO: Implement onCallback() method.
    }

    protected function genMessage(array $base){

    }
    
    protected function getTasks(?int $week = null, ?int $dayOfWeek = null) {
        $currentWeek = Week::getCurrentWeek();
        if($week === null) $week = $currentWeek;
        if($dayOfWeek === null) $dayOfWeek = Week::getCurrentDeyOfWeek();
    
        $getdays = [];
        if($dayOfWeek >= 5){
            $getdays[$dayOfWeek] = $week;
            if($dayOfWeek < $dayOfWeek) $getdays[$dayOfWeek+1] = $week;
            $getdays[1] = $week+1;
        }elseif ($dayOfWeek < 5){
            $getdays[$dayOfWeek] = $week;
            $getdays[$dayOfWeek+1] = $week;
        }
        dump(array_values($getdays));
        $tasks= Task::getByWeek($this->getClassId(), function ($query)use($getdays){
            return $query->whereIn('tasks.day', $values = array_keys($getdays))->whereIn('agenda.day', $values);
        }, array_values($getdays), false);

        $str = "";
        if(isset($tasks[-1])){
            $tasks[$currentWeek] = $tasks[-1];
            unset($tasks[-1]);
        }
        foreach ($getdays as $day => $week){
            $str .= "_".Week::getDayString($day)."_ ".(($currentWeek != $week) ? '('.Week::humanizeDayAndWeek($week, $day).')' : "").PHP_EOL;
            
            if(isset($tasks[$week][$day])){
                foreach ($tasks[$week][$day] as $task) {
                    ++$task['num'];
                    $str .= "{$task['num']}. *{$task['title']}*: _{$task['task']}_" . PHP_EOL; //TODO: add desc
                }
            }else{
                $str .= "empty".PHP_EOL;
            }
            $str .= PHP_EOL;
        }
//        foreach ($tasks_raw as $week => $weekTasks){
//            $str .= __('tgbot.tasks.day_title', ['date' => Week::getDtByWeekAndDay($week, $dayOfWeek)->format('d.m.Y'), 'weekday' => Week::getDayString($dayOfWeek)]) . PHP_EOL;
//            $dayTasks = [];
//            foreach ($weekTasks as $task){
//                if(!isset($dayTasks[$task['day']])) $dayTasks[$task['day']] = [];
//                $dayTasks[$task['day']][] = $task;
//            }
//
//            foreach ($dayTasks as $tasks) {

//            }
//        }

        return $str;
    }
}
