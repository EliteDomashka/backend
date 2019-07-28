<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
/**
 * @property int class_owner Владелец класса
 */
class User extends Model {
	public $incrementing = false;
	public $timestamps = true;
	protected $fillable = ['id', 'lang', 'class_owner'];
	protected $attributes = [
		'lang' => 'uk'
	];

	/**
	 * @return ClassM
	 */
	public function classM() {
		return $this->hasMany('App\ClassM', 'id', 'class_owner');
	}
}
