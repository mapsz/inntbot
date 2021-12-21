<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Tasks\EgrulNalog;
use App\Meta;

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
        file_put_contents('data.txt', $task->getError() );
        dd($task->getError());
      }
    }


  }

  public static function DBUpdate($inn, $data = []){

    dump('save');

    //Get model
    $mInn = Inn::where('inn', $inn)->first();

    //DB work
    try {
      DB::beginTransaction();
      
      {//Inn
        if(!$mInn){ //Make new
          $mInn = new Inn;
          $mInn->inn = $inn;
          $mInn->isEntity = self::isEntity($inn);
          $mInn->save();
        }else{ //Update
          $mInn->touch();
        }
      }

      {//Data
        $dataToPut = [];
        foreach ($data as $k => $v) {
          $put = $v;
          if(is_array($put)) $put = json_encode($put);
          $dataToPut[$k] = $put;
        }
        
        {//Remove same
          $jmInn = self::jGet($mInn->inn);
          $buffer = $dataToPut;
          foreach ($buffer as $k => $v) {
            if(Meta::get($jmInn->metas, $k) == $v){
              unset($dataToPut[$k]);
            } 
          }
        }

        //Store
        foreach ($dataToPut as $k => $v) {          
          $mInn->metas()->create(['name' => $k, 'value' => $v]);
        }

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

  public static function jGet($inn) {
    //Model
    $query = new self;
  
    {//With
      $query = $query->with(['metas' => function($q){
        $q->orderBy('created_at','DESC');
      }]); 
    }
  
    {//Where
      $query = $query->where('inn', $inn);
    }
  
    //Get
    $data = $query->first();

    
    {//Metas
      if(isset($data->metas)){
        $metas = $data->metas;
        $fMetas = [];
        unset($data->metas);      
  
        foreach ($metas as $key => $mata) {
          if(array_search($mata->name, array_column($fMetas, 'name')) === false) array_push($fMetas, $mata);     
        }
  
        $data['metas'] = $fMetas;
      }
    
    } 
  
    //Return
    return $data;
  }

  public static function isEntity($inn){
    return strlen($inn) == 10;
  }

  public static function _exists($inn){
    
    {//Get Inn
      $inn = self::getByInn($inn);
      if(!$inn) return false;
    }

    //Pre settings
    $meta = Meta::where('metable_type', 'App\Inn')->where('metable_id', $inn->id);

    //Check by name
    if(!$inn->isEntity){
      $meta = $meta->where('name', 'firstname');
    }else{
      $meta = $meta->where('name', 'full_name');
    }

    return $meta->exists();
    
  }

  public static function memberInExists($inn){
        
    {//Get Inn
      $inn = self::getByInn($inn);
      if(!$inn) return false;
    }

    //Pre settings
    $meta = Meta::where('metable_type', 'App\Inn')->where('metable_id', $inn->id);

    //MemberIn setting
    $meta = $meta->where('name', 'memberIn');

    return $meta->exists();


  }

  public static function isUpToDate($inn, $upToDateTime = 60 * 4){

    {//Get Inn
      $inn = self::getByInn($inn);
      if(!$inn) return false;
    }

    //Pre settings
    $meta = Meta::where('metable_type', 'App\Inn')->where('metable_id', $inn->id);

    //Check by name
    if(!$inn->isEntity){
      $meta = $meta->where('name', 'firstname');
    }else{
      $meta = $meta->where('name', 'full_name');
    }

    
    {//Get meta
      $meta = $meta->first();
      if(!$meta) return false;
    }



    return Carbon::parse($inn->updated_at)->diffInMinutes(now()) < $upToDateTime;

  }

  public static function stopsExists($inn){

    {//Get Inn
      $inn = self::getByInn($inn);
      if(!$inn) return false;
    }

    //Pre settings
    $meta = Meta::where('metable_type', 'App\Inn')->where('metable_id', $inn->id);

    //MemberIn setting
    $meta = $meta->where('name', 'stops');

    return $meta->exists();

  }

  public static function memberInIsToDate($inn){

    $upToDateTime = 60 * 4;

    {//Get Inn
      $inn = self::getByInn($inn);
      if(!$inn) return false;
    }

    //Pre settings
    $meta = Meta::where('metable_type', 'App\Inn')->where('metable_id', $inn->id);

    $meta = $meta->where('name', 'memberIn');
    
    {//Get meta
      $meta = $meta->first();
      if(!$meta) return false;
    }

    return Carbon::parse($meta->updated_at)->diffInMinutes(now()) < $upToDateTime;
  }

  public static function stopsIsToDate($inn){

    $upToDateTime = 60 * 4;

    {//Get Inn
      $inn = self::getByInn($inn);
      if(!$inn) return false;
    }

    //Pre settings
    $meta = Meta::where('metable_type', 'App\Inn')->where('metable_id', $inn->id);

    $meta = $meta->where('name', 'stops');
    
    {//Get meta
      $meta = $meta->first();
      if(!$meta) return false;
    }

    return Carbon::parse($meta->updated_at)->diffInMinutes(now()) < $upToDateTime;
  }

  public static function getByInn($inn){
    if(gettype($inn) == 'string' || gettype($inn) == 'integer') $inn = Inn::where('inn', $inn)->first();
    if(!$inn) return false;
    return $inn;
  }

  public function metas(){
    return $this->morphMany('App\Meta', 'metable');
  }  
}
