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

		MagicCommand::$user = null;
		MagicCommand::$class = null;
		if ($response = $bot->processUpdate($upd = new Update(request()->all(), $bot->getBotUsername()))) {
			return response((string)$response->isOk());
		}
	}
	public function set(PhpTelegramBotContract $bot){
		dump($bot->setWebhook('https://'.env('APP_DOMAIN') . '/api/tgbot'));
	}
}
