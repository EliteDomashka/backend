<?php

namespace App\Listeners;

use App\Events\TaskCreated;
use App\Events\TaskEdited;

class CreateTaskListener extends EditTaskListener{

    /**
     * Handle the event.
     *
     * @param  TaskCreated  $event
     * @return void
     */
    public function handle(TaskEdited $event) {
        parent::handle($event);
    }
}
