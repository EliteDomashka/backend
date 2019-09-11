<?php
namespace App\Console\Commands;
require app_path('Telegram/Commands/TasksCommand.php');


use App\ClassM;
use App\Telegram\Helpers\Week;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Longman\TelegramBot\Commands\UserCommands\TasksCommand;
use Longman\TelegramBot\Request;
use PhpTelegramBot\Laravel\PhpTelegramBotContract;

class SendDailyTasks extends Command{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'daily:sendAll';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct() {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle(PhpTelegramBotContract $bot) {
        dump('daily');
        dump(date('d.m.Y'));
        $dt = Carbon::now();
        $dt->hours($dt->hour-3);
        $startdt = (clone $dt)->startOfDay();
        dump($startdt);
        dump($dt);
        $diff = $startdt->diffInSeconds($dt);
        dump($diff);
        dump(config('app.timezone'));
        
        $week = Week::getCurrentWeek();
        $dayOfWeek = (Week::getCurrentDayOfWeek()+1 < 6) ? Week::getCurrentDayOfWeek() : 1;
        dump($week);
            dump($dayOfWeek);
        $chats = ClassM::select('classes.id as class_id', 'notify_chat_id', 'user_owner')
            ->where([
                ['notify_time', '<=', $diff+60],
                ['notify_time', '>=', $diff-60]
            ])
            ->get();
        
        dump(json_encode($chats));
        foreach ($chats as $chat){
            $daily_task = DB::table('daily_tasks')->select('message_id')->where([
                ["class_id", "=", $chat['class_id']],
                ['dayOfWeek', "=", $dayOfWeek]
            ])->where('week', $week)->first('message_id');
            
            $tasks = TasksCommand::getTasks($chat['class_id'], false, $week, $dayOfWeek, false, true);
            
            dump(json_encode($tasks));
            if(!isset($daily_task->message_id)){
                $resp = Request::sendMessage([
                    'chat_id' => $chat['notify_chat_id'] ?? $chat['user_owner'],
                    'text' => $tasks,
                    'parse_mode' => 'markdown'
                ]);
                
                dump(json_encode($resp));
                if($resp->isOk()) {
                    DB::table('daily_tasks')->insert([
                        'class_id' => $chat['class_id'],
                        'message_id' => $resp->getResult()->message_id,
                        'dayOfWeek' => $dayOfWeek,
                        'week' => Week::getCurrentWeek()
                    ]);
                }else{
                    dump($resp);
                }
            }
        }
        
        return 'ok';
    }
}
