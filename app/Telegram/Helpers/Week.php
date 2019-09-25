<?php
namespace App\Telegram\Helpers;


use Carbon\Carbon;

class Week {
	public static $days= [
		1 => "Понеділок",
		2 => "Вівторок",
		3 => "Середа",
		4 => "Четвер",
		5 => "Пятниця",
		6 => "Суббота"
	];

	public static function getDayString($day) :?string {
		return self::$days[$day];
	}

	/**
	 * Выдаст на выходе строку вида 29.07.2019-4.08.2019
	 * @param int $week неедля от 1
	 * @return string
	 */
	public static function humanize(int $week): string {
		$str = '(';
		$dt = ($year_start = Carbon::now()->startOfYear())->addDays(($week*7)-$year_start->day-7)->startOfWeek(); //-7 ткк недели идут от 0, тоесть иначе $week-1
		$str .= $dt->format($format = 'd.m.Y') . '-'. $dt->endOfWeek()->format($format).')';
		return $str;
	}

	public static function getDtByWeekAndDay(int $week, int $dayOfWeek): Carbon{
	    $dt = Carbon::now();
	    $dt->week = $week;
	    $dt->startOfWeek()->addDays($dayOfWeek-1);
	    return $dt;
    }

    public static function getWeekByTimestamp(int $timestamp) : int {
	    return Carbon::createFromTimestamp($timestamp)->week;
    }

	public static function humanizeDayAndWeek(int $week, int $day): string {
		$dt = ($year_start = Carbon::now()->startOfYear())->addDays(($week*7)-$year_start->day-7)->startOfWeek()->addDays($day-1);
		return $dt->format('d.m.Y');
	}
    
    public static function getCurrentWeek(): int {
        return date('W');
    }
    
	public static function getCurrentDayOfWeek(): int {
	    return Carbon::now()->dayOfWeekIso;
    }
}
