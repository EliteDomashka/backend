<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

/**
 * Class Agenda
 * @property int week
 */
class Agenda extends Model {
    protected $table = 'agenda';
    protected $fillable = ['class_id', 'day', 'num', 'lesson_id', 'week'];
    public $timestamps = false;

    public static function getSchedule(int $class_id){
    	return Agenda::where('class_id', $class_id)->select('lessons_id.title', 'day', 'num' )->leftJoin('lessons_id', 'agenda.lesson_id', '=', 'lessons_id.id');
    }
}
