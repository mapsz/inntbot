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
      if(!isset($request->name) || $request->name == ''){
        return false;
      }        
    }

    

    Task::add($request->name);

    return response()->json(1, 200);

  }


  public function task(Request $request){

    dd(11);
  }

}
