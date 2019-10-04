<?php

namespace App;

use App\Events\TaskEdited;
use App\Telegram\Helpers\TaskCropper;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * @property int day dayOfWeek
 * @property int num lesson num of day
 * @property int week
 * @property string task
 * @property string desc
 * @property int class_id
 */

class Task extends Model{
    protected $table = 'tasks';
    public $timestamps = true;
    public $incrementing = true;
    protected $fillable = ['num', 'day', 'week', 'desc', 'class_id', 'task', 'desc', 'chat_user_msg_id', 'author_id'];

    public static function add(int $class_id, int $author_id, int $msg_id, int $lesson_num, int $dayOfWeek, int $week, string $task, string $desc = null): Task {
            return Task::create([
                'num' => $lesson_num,
                'day' => $dayOfWeek,
                'week' => $week,
                'task' => $task,
                'author_id' => $author_id,
                'chat_user_msg_id' => $msg_id,
                'class_id' => $class_id,
                'desc' => $desc,
            ]);
    }
    public static function edit(?int $class_id = null, int $chat_user_msg_id, int $author_id, string $task, string $desc = null):  int {
        $base = Task::where([
            ['chat_user_msg_id', $chat_user_msg_id],
            ['author_id', $author_id]
        ]);
        if($class_id != null) $base->where('class_id', $class_id);

        /** @var $taskM Task  */
        $taskM = $base->first();

        $taskM->task = $task;
        $taskM->desc = $desc;

        $resp = $taskM->save();
        event(new TaskEdited($taskM));
        return $resp;
    }

    public static function getByWeek(?int $class_id, callable $queryCall, ?int $week, bool $raw = false, $fullDesc = false){
        return Agenda::getScheduleForWeek($class_id, function ($query)use($week, $queryCall, $class_id, $fullDesc){
            $base = $query->join('tasks', function ($join)use($week){
                $join = $join->on([
                    ['agenda.num', '=', 'tasks.num'],
                    ['agenda.day', '=', "tasks.day"],
                    ['agenda.class_id', '=', "tasks.class_id"],
                    ]);
                if($week != null){
                    $join->where('tasks.week', $week);
                }
            })->addSelect('tasks.task')->addSelect('tasks.week as tweek');
            if($fullDesc) $base->addSelect('tasks.desc');
            else $base->addSelect(DB::raw('CASE
            WHEN (tasks.desc IS null) AND (CHAR_LENGTH(tasks.desc) < ' . TaskCropper::MAX . ') THEN tasks.desc
            WHEN (tasks.desc IS NOT null) AND (tasks.desc <> \'\') THEN \'1\'
            ELSE null
            END AS desc'));
            return $queryCall($base);
        }, $week, $raw);
    }

    public static function getById(int $task_id){
        $val = self::getByWeek(null, function ($query)use($task_id){
           return $query->where('tasks.id', $task_id);
        }, null, true, true);
        if(empty($val)) return null;

        $week = array_shift($val);
        if(is_array($val = array_shift($week))) return $val;

        return null;
    }
}
