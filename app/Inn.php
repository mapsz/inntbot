<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Inn extends Model
{
  public $guarded = [];

  public static function make($inn, $data){

    //Put Inn
    $put = new Inn;
    $put->inn   = $inn;
    $put->isEntity = self::isEntity($inn);
    $put->save();

    dd($data);

  }

  public static function isEntity($inn){
    return strlen($inn) == 10;
  }


  public function metas(){
    return $this->morphMany('App\Meta', 'metable');
  }  
}
