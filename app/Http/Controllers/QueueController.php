<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Log;
use App\Task;
use App\TaskMeta;

class QueueController extends Controller
{
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

  public function addWork(Request $request){



  }
}
