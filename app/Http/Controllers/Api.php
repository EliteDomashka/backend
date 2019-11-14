<?php


namespace App\Http\Controllers;


use App\Agenda;
use App\Attachment;
use App\Task;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class Api extends Controller {
	public function getWeek(Request $request, int $week){
		return response()->json(['response' => Task::getByWeek($request->get('class_id'), function($queryCall){
			return $queryCall->addSelect("tasks.id as task_id");
		}, $week, false, true)]);
	}

	public function getAgenda(Request $request, int $week){
		return response()->json(['response' => Agenda::getScheduleForWeek($request->get('class_id'), function($queryCall){
			return $queryCall;
		}, $week, false, true, true)]);
	}

	public function getFullWeek(Request $request, int $week){
		$agenda = Agenda::getScheduleForWeek($request->get('class_id'), function($queryCall){
			return $queryCall;
		}, $week, false, true, true);
		$tasks =  Task::getByWeek($request->get('class_id'), function($queryCall){
			return $queryCall->addSelect("tasks.id as task_id");
		}, $week, false, true);

		foreach ($agenda as &$days){
			foreach ($days as &$lesson){
				if(isset($tasks[$lesson['day']])){
					foreach ($tasks[$lesson['day']] as $task){
						$attachment_have = Attachment::where('task_id', $task['task_id'])->exists();
						$task['attachment_have'] = $attachment_have;
						if($task['num'] == $lesson['num']) $lesson = array_merge($lesson, $task);
					}
				}
			}
		}

		return response()->json(['response' => $agenda]);
	}

	public function getAttachment(int $task_id, int $attachment_id){
		$attachment = Attachment::where([
			['task_id', $task_id],
			['id', $attachment_id]
		])->firstOrFail();
		$filename = $attachment->filename ?: ($attachment->type == "Photo" ? "photo.jpg" : $attachment_id);
		return Storage::cloud()->download(Attachment::PATH."{$task_id}/{$attachment_id}", $filename);
	}

	public function getAttachments(int $task_id){
		return response()->json(['response' => Attachment::where('task_id', $task_id)->get()]); //TODO: add check of class_id
	}
}
