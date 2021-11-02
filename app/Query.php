<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

use App\Task;

class Query extends Model
{

  public static function newTask($site, $name, $metas){

    {//Make task
      $task = new Task;
      $task->site = $site;
      $task->name = $name;
      $task->status = 0;
      $task->save();
    }

    //Add metas
    foreach ($metas as $key => $value) {

      DB::table('task_metas')->insert([
        'task_id'     => $task->id,
        'name'        => $key,
        'value'       => $value
      ]);

    }

    $task = Task::with('metas')->where('id', $task->id)->first();

    return $task;

  }
}
