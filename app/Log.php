<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Log extends Model
{
  
  public function metas(){
    return $this->morphMany('App\Meta', 'metable');
  }  
}
