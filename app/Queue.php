<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

use App\Task;
use App\Meta;
use App\Inn;
use App\Tasks\EgrulNalog;
use Carbon\Carbon;

class Queue extends Model
{
  public static function addWork($work, $inn, $metas = []){

    switch ($work) {
      case 'FullInnUpdate':

        try {
          DB::beginTransaction();

          {//Egrul
            $task = new Task;
            $task->name = 'InnUpdate';
            $task->status = 0;
            $task->save();
            $task->metas()->create(['name' => 'inn', 'value' => $inn]);
          }

          {//Members
            $task = new Task;
            $task->name = 'MemberInUpdate';
            $task->status = 0;
            $task->save();
            $task->metas()->create(['name' => 'inn', 'value' => $inn]);
          }

          //Store to DB
          DB::commit();    
        } catch (Exception $e) {
          // Rollback from DB
          DB::rollback();
          die($e);
        }

      break;
      default:
        # code...
        break;
    }

  }

  public static function doActualTask(){
    self::runTask(self::getActualTask());
  }

  public static function getActualTask(){

    $task = Task::with('metas')
    ->where('status', '<', '1')
    ->orderBy('status','ASC')
    ->orderBy('priority','DESC')
    ->orderBy('created_at','ASC')
    ->first();

    if(!$task){dump('no task'); return false;}

    return $task;

  }

  public static function runTask($task){

    //Get task
    if(!isset($task->id)){
      if(!$task) return 0;
      $task = Task::with('metas')->find($task);
    }

    {//Log
      $log = new Log;
      $log->name = $task->name;
      $log->task_id = $task->id;
      $log->save();
    }

    switch ($task->name) {
      case 'InnUpdate':

        $inn = Meta::get($task->metas, 'inn');

        //Get
        $mInn = Inn::where('inn', $inn)->first();
        $update = false;

        //Exists
        if(!$mInn) $update = true;

        //Recent updated
        if($update || Carbon::now()->diffInMinutes($mInn->updated_at) > 5)  $update = true;
        
        {//Update
          $run = true;
          if($update){
            $eTask = new EgrulNalog($inn);
            $run = $eTask->run();
          }
        }

        {//Log          
          {//task
            $task->status = $run ? 1 : $task->status-1;       
          }
          //Log
          if(!$run){
            $log->status = $task->getError();
            $log->result = 0;
          }
        }

      break;
      case 'MemberInUpdate':
        
        $inn = Meta::get($task->metas, 'inn');

        $mInn = Inn::where('inn', $inn)->first();

        //Make Inn
        if(!$mInn){

        }

      
      default:
        {//Log          
          //task
          $task->status--;
          $log->status = 0;
          $log->result = 'bad task';
        }
      break;
    }

    {//Log result
      //Task
      $task->save();

      $log->status = $log->status === null ? 1          : $log->status;
      $log->result = $log->result === null ? 'success'  : $log->result;
      $log->save();
    }

    return true;
  }


  
}
