<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

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
    protected $fillable = ['num', 'day', 'week', 'desc', 'class_id', 'task', 'desc'];

    public static function add(int $class_id, int $lesson_num, int $dayOfWeek, int $week, string $task, string $desc = ""): Task {
            return Task::create([
                'num' => $lesson_num,
                'day' => $dayOfWeek,
                'week' => $week,
                'task' => $task,
                'class_id' => $class_id,
                'desc' => $desc
            ]);
    }
}
