<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;
/**
 * Class Agenda
 * @property int week
 */
class Agenda extends Model {
    protected $table = 'agenda';
    protected $fillable = ['class_id', 'day', 'num', 'lesson_id', 'week'];
    public $timestamps = false;

    public static function getSchedule(int $class_id, bool $asTitle = true){
    	$base =  Agenda::where('class_id', $class_id)->select('day', 'num' )->orderBy('num', 'asc')->orderBy('day', 'asc');
    	if($asTitle){
            return $base->leftJoin('lessons_id', 'agenda.lesson_id', '=', 'lessons_id.id')->addSelect('lessons_id.title as title');
        }else{
    	    return $base->addSelect('lesson_id');
        }
    }

    public static function getScheduleForWeek(int $class_id, callable $query, ?int $week = null, $raw = false, bool $asTitle = true): array {
        if($week === null) $week = (int)date('W');
        $lessons = $query(Agenda::getSchedule($class_id, $asTitle)->addSelect('week')->whereIn('week', [$week, -1]))->get();
        $new = [];
        foreach ($lessons as $lesson){
            if(!isset($new[$lesson['week']])) $new[$lesson['week']] = [];
            if(!$raw) {
                if (!isset($new[$lesson['week']][$lesson['day']])) $new[$lesson['week']][$lesson['day']] = [];
                if (!isset($new[$lesson['week']][$lesson['day']][$lesson['num']])) $new[$lesson['week']][$lesson['day']][$lesson['num']] = $lesson->toArray();
            }else{
                $new[$lesson['week']][] = $lesson->toArray();
            }
        }
        $lessons = $new;
        dump('returned: '.isset($lessons[$week]) ? $week : -1);
        return $lessons[isset($lessons[$week]) ? $week : -1];
    }

    public static function findNextLesson(int $class_id, int $day, int $num): array {
        $currentWeek = (int)date('W');
        // --- start get lesson_id by day and num ----
        $lesson_id = Agenda::getScheduleForWeek($class_id, function ($query)use($day, $num){
            return $query->where([
                ['day', '=', $day],
                ['num', '=', $num]
            ]);
        }, $currentWeek, true, false)[0]['lesson_id'];
        // --- end get lesson_id by day and num ----

        $lessons = Agenda::getScheduleForWeek($class_id, function ($query)use($lesson_id){
            return $query->where('lesson_id', $lesson_id);
        }, $currentWeek, true);

        $dt = Carbon::now(new \DateTimeZone('Europe/Kiev'));
        dump($dt->dayOfWeekIso);

        $result = [];
        foreach ($lessons as $lesson){ //поиск на этой неделе
            if(empty($result)){
                dump($lesson);
                if($lesson['day'] > $dt->dayOfWeekIso){
                    dump($lesson);
                    echo 'ok';
                    $dt->addDays($lesson['day'] - $dt->dayOfWeekIso);
                    $result = $lesson;
                }
            }
        }
        dump($result);
        echo "поиск на следующей неделе";
        if(empty($result)){// поиск на следующей неделе
            $lessons = Agenda::getScheduleForWeek($class_id, function ($query)use($lesson_id){
                return $query->where('lesson_id', $lesson_id);
            }, $currentWeek+1, true);
            foreach ($lessons as $lesson){
                if(empty($result) && ($lesson['day'] < $dt->dayOfWeekIso)){
                    $dt->addWeek();
                    if($dt->dayOfWeekIso  > $lesson['day']){
                        $dt->day = $dt->day - ($dt->dayOfWeekIso-$lesson['day']);
                    }else{ //кажеться это лишние
                        $dt->day = $dt->day + ($lesson['day'] - $dt->dayOfWeekIso);
                    }

                    $result = $lesson;
                }
            }
        }

        if(empty($result) && count($lessons) == 1){ //если один урок в неделю
            $result = $lessons[0];
            $dt->addWeek();
        }
        dump($lessons);
        if(!is_array($result)) $result = $result->toArray();
        $result['date'] = $dt->format("Y-m-d");
        $result['timestamp'] = $dt->getTimestamp();
        return $result;
    }

    public function addTitle(){
        return $this->leftJoin('lessons_id', 'agenda.lesson_id', '=', 'lessons_id.id')->addSelect('lessons_id.title as title');
    }

}
