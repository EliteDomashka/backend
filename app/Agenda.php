<?php

namespace App;

use App\Telegram\Helpers\Week;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Class Agenda
 * @property int week
 */
class Agenda extends Model {
    protected $table = 'agenda';
    protected $fillable = ['class_id', 'day', 'num', 'lesson_id', 'week'];
    public $timestamps = false;

    public static function getSchedule(?int $class_id, bool $asTitle = true){
    	$base =  Agenda::select('agenda.day', 'agenda.num' )->orderBy('agenda.day', 'asc')->orderBy('agenda.num', 'asc');
    	if($class_id != null) $base->where('agenda.class_id', $class_id);
    	if($asTitle){
            return $base->leftJoin('lessons_id', 'agenda.lesson_id', '=', 'lessons_id.id')->addSelect('lessons_id.title as title');
        }else{
    	    return $base->addSelect('agenda.lesson_id');
        }
    }

    /**
     * @param int $class_id
     * @param callable $query
     * @param null|array|int $week
     * @param bool $raw
     * @param bool $asTitle
     * @return array|Collection
     */
    public static function getScheduleForWeek(?int $class_id, callable $query, $week = -1, $raw = false, bool $asTitle = true) {
        if($week === -1) $week = Week::getCurrentWeek();
        if(is_array($week)){
            $week[] = -1;
        }
        $lessons = $query(Agenda::getSchedule($class_id, $asTitle)->addSelect('agenda.week'));
        if($week != null) $lessons = $lessons->whereIn('agenda.week', is_numeric($week) ? [$week, -1] : $week);
        $lessons = $lessons->get();

        $new = [];
        foreach ($lessons as $lesson){
            $_week = (isset($lesson['tweek']) ? $lesson['tweek'] : $lesson['week']);

            if(!isset($new[$_week])) $new[$_week] = [];
            if(!$raw) {
                unset($lesson['week']);

                if (!isset($new[$_week][$lesson['day']])) $new[$_week][$lesson['day']] = [];
//                if (!isset($new[$_week][$lesson['day']][$lesson['num']])) $new[$_week][$lesson['day']][$lesson['num']] = $lesson->toArray();
                $new[$_week][$lesson['day']][] = $lesson->toArray();
            }else{
                $new[$_week][] = $lesson->toArray();
            }
        }

        $lessons = $new;
        if(is_array($week)) return $lessons;
        if(empty($lessons)) return [];

        return $lessons[isset($lessons[$week]) ? $week : (isset($lessons[-1]) ? -1 : ($week == null ? array_keys($lessons)[0] : null))];
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
        }, $currentWeek, true, false);

        $dt = Carbon::now(new \DateTimeZone('Europe/Kiev'));

        $result = [];
        foreach ($lessons as $lesson){ //поиск на этой неделе
            if(empty($result)){
                if($lesson['day'] > $dt->dayOfWeekIso){
                    $dt->addDays($lesson['day'] - $dt->dayOfWeekIso);
                    $result = $lesson;
                }
            }
        }

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

        if(!is_array($result)) $result = $result->toArray();
        $result['date'] = $dt->format("Y-m-d");
        $result['timestamp'] = $dt->getTimestamp();
        $result['lesson_id'] = $lesson_id;
        return $result;
    }
}
