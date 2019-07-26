<?php
namespace App\Http\Controllers;


use Illuminate\Support\Facades\Log;
use Longman\TelegramBot\Entities\Update;
use Longman\TelegramBot\Request;
use Longman\TelegramBot\TelegramLog;
use PhpTelegramBot\Laravel\PhpTelegramBotContract;

class Telegram extends Controller {
    public function handle(PhpTelegramBotContract $bot){
    	Log::info('test');
    	error_reporting(E_ALL);

    	try {
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