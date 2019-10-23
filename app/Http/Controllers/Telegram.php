<?php
namespace App\Http\Controllers;


use App\Telegram\Commands\MagicCommand;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Longman\TelegramBot\Entities\Update;
use Longman\TelegramBot\Request;
use Longman\TelegramBot\TelegramLog;
use PhpTelegramBot\Laravel\PhpTelegramBotContract;

class Telegram extends Controller {
	public function handle(PhpTelegramBotContract $bot){
		error_reporting(E_ALL);
		dump(config('app.timezone'));
//        date_default_timezone_set(config('app.timezone'));
		try {
			MagicCommand::$user = null;
			MagicCommand::$class = null;
			if ($response = $bot->processUpdate($upd = new Update(request()->all(), $bot->getBotUsername()))) {
				return response((string)$response->isOk());
			}
		} catch (\Exception $e) {
			report($e);
		}
//        Log::info(app_path('Telegram/Commands'));
	}
	public function set(PhpTelegramBotContract $bot){
		dump($bot->setWebhook('https://backend.domashka.cloud/api/tgbot'));
//        $bot->handle();
	}
}
