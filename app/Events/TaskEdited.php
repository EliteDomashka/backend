<?php

namespace App\Events;

use App\Task;
use Illuminate\Queue\SerializesModels;

class TaskEdited{
    use SerializesModels;

    /** @var Task */
    public $task;
    
    
    public function __construct(Task $task) {
        $this->task = $task;
    }


}
