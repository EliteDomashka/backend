<?php
namespace App\Telegram\Helpers;


class Week {
	public static $days= [
		1 => "Понеділок",
		2 => "Вівторок",
		3 => "Середа",
		4 => "Четверг",
		5 => "Пятниця",
		6 => "Суббота"
	];
	public static function getDayString($day) {
		return self::$days[$day];
	}
}