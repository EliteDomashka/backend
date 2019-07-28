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
}
