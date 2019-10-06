<?php


namespace App\Tasks;


use Hhxsv5\LaravelS\Swoole\Task\Task;
use Illuminate\Support\Facades\Log;

class AttachmentUploaderTask extends Task {
    private $task_id;
    private $attachment_id;
    private $file_id;
    private $result;
    public function __construct(int $task_id, int $attachment_id, string $file_id) {
        $this->task_id = $task_id;
        $this->attachment_id = $attachment_id;
        $this->file_id = $file_id;
    }
    // The logic of task handling, run in task process, CAN NOT deliver task
    public function handle() {
        Log::info(__CLASS__ . ':handle start', [$this->task_id]);
        sleep(2);// Simulate the slow codes
        Log::info(__CLASS__ . ':handle test', [env('PHP_TELEGRAM_BOT_API_KEY')]);
        // throw new \Exception('an exception');// all exceptions will be ignored, then record them into Swoole log, you need to try/catch them
        $this->result = 'the result of ' . $this->task_id;
    }
    // Optional, finish event, the logic of after task handling, run in worker process, CAN deliver task
    public function finish() {
        Log::info(__CLASS__ . ':finish start', [$this->result]);
    }
}
