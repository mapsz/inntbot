<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

use App\Task;


class TaskController extends Controller
{

  //http://inntbot.loc/put/task?site=%27egrul.nalog.ru%27&name=inn&inn=1233213123
  public function put(Request $request){

    {//Get Site Name
      if(
        !isset($request->site) || $request->site == '' ||
        !isset($request->name) || $request->name == ''
      ){
        return false;
      }        
      $site = $request->site;
      $name = $request->name;
    }

    
    {//Get Meta 
      $metas = $request->all();
      unset($metas['site']);
      unset($metas['name']);
    }
    

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

    // dd($task);

    // return $task;   

  }


}
