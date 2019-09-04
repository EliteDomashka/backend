<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
/**
 * @property int id
 * @property int user_owner Владелец класса
 * @property int chat_id id чата в телегамме
 * @property int class_num № класса
 */
class ClassM extends Model {
	protected $table = 'classes';
	protected $fillable = ['class_num', 'domain', 'user_owner', 'chat_id'];
	public $timestamps = false;

	public function agenda() {
		return $this->hasMany('App\Agenda', 'id', 'class_id');
	}
	public static function getByDomain(string $domain){
	   return self::where('domain', $domain)->first();
    }
}
