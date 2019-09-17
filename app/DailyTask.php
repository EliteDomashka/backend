<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class DailyTask extends Model{
    protected $table = 'daily_tasks';
    protected $fillable = ['class_id', 'message_id', 'dayOfWeek', 'week'];
    public $timestamps = true;
    public $incrementing = true;
}
