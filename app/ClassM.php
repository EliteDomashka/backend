<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class ClassM extends Model {
	protected $table = 'classes';
	protected $fillable = ['class_num', 'domain'];
	public $timestamps = false;

	public function agenda() {
		return $this->hasMany('App\Agenda', 'id', 'class_id');
	}
	public static function getByDomain(string $domain){
	   return self::where('domain', $domain)->first();
    }
}
