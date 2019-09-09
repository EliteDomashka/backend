<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
/**
 * @property int id
 * @property int user_owner Владелец класса
 * @property int chat_id id чата в телегамме
 * @property int class_num № класса
 * @property int notify_chat_id чат в тг, если пусто - лс
 * @property int notify_time время в секнудах от начала дня
 */
class ClassM extends Model {
	protected $table = 'classes';
	protected $fillable = ['class_num', 'domain', 'user_owner', 'chat_id', 'notify_time', 'notify_chat_id', 'notify_pin'];
	public $timestamps = false;

	public function agenda() {
		return $this->hasMany('App\Agenda', 'id', 'class_id');
	}
	public static function getByDomain(string $domain){
	   return self::where('domain', $domain)->first();
    }
}
