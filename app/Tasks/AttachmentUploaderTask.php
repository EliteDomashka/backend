<?php


namespace App\Tasks;


use App\Attachment;
use App\Telegram\Helpers\AttachmentHelper;
use GuzzleHttp\Client;
use Hhxsv5\LaravelS\Swoole\Task\Task;
use Illuminate\Cache\CacheManager;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Longman\TelegramBot\Entities\Document;
use Longman\TelegramBot\Entities\File;
use Longman\TelegramBot\Request;
use PhpTelegramBot\Laravel\PhpTelegramBotContract;

class AttachmentUploaderTask extends Task {
	private $task_id;
	private $attachment_id;
	private $file_id;
	private $file_path;
	private $file_type;
	private $caption;

	private $result;
	public function __construct(int $task_id, int $attachment_id, string $file_id, string $file_type, ?string $caption) {
		$this->task_id = $task_id;
		$this->attachment_id = $attachment_id;
		$this->file_id = $file_id;
		$this->caption = $caption;

		$file = Request::getFile([ //TODO: call in handle
			'file_id' => $this->file_id
		]);
		$this->file_path = $file->getResult()->file_path;
		$this->file_type = AttachmentHelper::toType($file_type);
	}
	// The logic of task handling, run in task process, CAN NOT deliver task
	public function handle() {

		$httpclient = new Client();
		$request = $httpclient->request("GET", $url = 'https://api.telegram.org/file/bot'. env('PHP_TELEGRAM_BOT_API_KEY'). '/' . $this->file_path);
		dump($url);
		dump($request->getBody());

		if($request->getStatusCode() == 200){
			dump('save');
			try {
				$resp = Storage::cloud()->put($path = "/attachments/{$this->task_id}/{$this->attachment_id}", $request->getBody()->getContents()); // ['visibility' => 'public']);
				Attachment::create($this->task_id, $this->attachment_id, $this->file_type, $this->file_id, $this->caption );

			}catch (\Exception $exp){
				Log::info($exp);
			}
		}

		Log::info(__CLASS__ . ':handle start', [$this->task_id]);

	}
	// Optional, finish event, the logic of after task handling, run in worker process, CAN deliver task
	public function finish() {
		Log::info(__CLASS__ . ':finish start', [$this->result ?? 'null']);
	}
}
