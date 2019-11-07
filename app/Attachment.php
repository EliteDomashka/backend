<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int task_id
 * @property int id
 * @property string type
 * @property string file_id
 * @property string caption
 * @property string filename
 * @property int filesize
 */

class Attachment extends Model{
    protected $table = 'attachments';
    public $incrementing = true;
    public $timestamps = true;

    protected $fillable = ['task_id', 'id', 'type'];


    const PATH = "attachments/";
    public static function create(int $task_id, int $attachment_id, string $type, string $file_id, ?string $caption, string $filename, int $filesize){
        $attachment = new Attachment();
        $attachment->task_id = $task_id;
        $attachment->id = $attachment_id;
        $attachment->type = $type;
        $attachment->file_id = $file_id;
        $attachment->caption = $caption;
        $attachment->filename = $filename;
        $attachment->filesize = $filesize;

        $attachment->save();
    }
}
