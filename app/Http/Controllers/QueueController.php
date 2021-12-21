<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Log;
use App\Task;
use App\TaskMeta;
use App\Queue;

class QueueController extends Controller
{

  public function doActualTask(Request $request){
    Queue::doActualTask();
  }

  public function addWork(Request $request){

    {//Get data
      if(!isset($request->work) || $request->work == ''){
        return false;
      }        
      if(!isset($request->inn) || !$request->inn){
        return false;
      }        
    }

    Queue::addWork($request->work, $request->inn);

    return response()->json(1, 200);

  }

  

  public function runTask(Request $request){

    {//Get task
      $task = Task::with('metas')
      ->where('status', '<', '1')
      ->orderBy('priority','DESC')
      ->orderBy('created_at','ASC')
      ->first();

      if(!$task){dd('no task');}

      $metas = TaskMeta::beautify($task->metas);
    }
    
    {//Model
      $modelName = "App\Tasks\\" . $task->name;
      $model = new $modelName;
    }
    
    {//Run task

      dump($task->name);
      dump($metas);
      
      {//Log
        $log = new Log;
        $log->name = $task->name;
        $log->task_id = $task->id;
      }

      //Run
      $run = $model->run($metas);

      //Failed
      if(!$run){

        //Log
        $log->status = -1;
        $log->result = $model->getError();

        //Task
        $task->status = -1;

        //Dump
        dump($model->getError());
      }
      
      //Success
      else{
        //Log
        $log->status = 1;
        $log->result = 'Success';

        //Task
        $task->status = 1;

        //Dump
        dump('Success');
      }

      //Log
      $log->save();

      //Task
      $task->save();

    }

  }
}
