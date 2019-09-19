<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int dayOfWeek
 * @property int message_id
 * @property int week
 * @property int chat_id
 * @property int class_id
 */
class DailyTask extends Model{
    protected $table = 'daily_tasks';
    protected $fillable = ['class_id', 'message_id', 'dayOfWeek', 'week', 'chat_id'];
    public $timestamps = true;
    public $incrementing = true;
}
