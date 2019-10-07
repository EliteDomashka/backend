<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int task_id
 * @property int id
 * @property string type
 */

class Attachment extends Model{
    protected $table = 'attachments';
    public $incrementing = true;
    public $timestamps = true;
    
    protected $fillable = ['task_id', 'id', 'type'];

    public static function create(int $task_id, int $attachment_id, string $type){
        $attachment = new Attachment();
        $attachment->task_id = $task_id;
        $attachment->id = $attachment_id;
        $attachment->type = $type;
        
        $attachment->save();
    }
}
