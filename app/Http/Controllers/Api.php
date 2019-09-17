<?php


namespace App\Http\Controllers;


use App\Agenda;
use App\Task;
use Illuminate\Http\Request;

class Api extends Controller {
    public function getWeek(Request $request, int $week){
        return response()->json(['response' => Task::getByWeek($request->get('class_id'), function($queryCall){
            return $queryCall->addSelect("tasks.id as task_id");
        }, $week, false, true)]);
    }
    public function getAgenda(Request $request, int $week){
        return response()->json(['response' => Agenda::getScheduleForWeek($request->get('class_id'), function($queryCall){
            return $queryCall;
        }, $week)]);
    }
}
