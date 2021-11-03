<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Tasks\EgrulNalog;

class Inn extends Model
{
  public $guarded = [];

  public static function innUpdate($inn){

    //Get
    $mInn = self::where('inn', $inn)->first();

    $update = false;

    //Exists
    if(!$mInn) $update = true;

    //Recent updated
    if($update || Carbon::now()->diffInMinutes($mInn->updated_at) > 5)  $update = true;

    //Update
    if($update){
      dump($inn);
      $task = new EgrulNalog($inn);
      if(!$task->run()){
        dd($task->getError());
      }
    }


  }

  public static function DBUpdate($inn, $data){

    //Get model
    $mInn = Inn::where('inn', $inn)->first();

    //DB work
    try {
      DB::beginTransaction();

      if(!$mInn){ //Make new
        $mInn = new Inn;
        $mInn->inn = $inn;
        $mInn->isEntity = self::isEntity($inn);
        $mInn->save();
      }else{ //Update
        $mInn->touch();
      }
  
      //Update data
      $dataToPut = [];
      foreach ($data as $k => $v) {
        $put = $v;
        if(is_array($put)) $put = json_encode($put);
        $dataToPut[$k] = $put;
      }

      //Store
      foreach ($dataToPut as $k => $v) {
        $mInn->metas()->create(['name' => $k, 'value' => $v]);
      }
      
      //Store to DB
      DB::commit();    
    } catch (Exception $e) {
      // Rollback from DB
      DB::rollback();
      die($e);
    }

    return $mInn;

  }

  public static function isEntity($inn){
    return strlen($inn) == 10;
  }


  public function metas(){
    return $this->morphMany('App\Meta', 'metable');
  }  
}
