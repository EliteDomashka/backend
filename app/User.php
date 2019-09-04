<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int id
 * @property int lang язык, uk/ru
 */

class User extends Model {
	protected $primaryKey = 'id';
	public $incrementing = false;
	public $timestamps = true;
	protected $fillable = ['id', 'lang'];
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
