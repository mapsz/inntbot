<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Task extends Model
{
  
  private $site;

  function __construct() {

  }

  public static function runTask($name){
    // EgrulNalog

  }


  public function saveData($data){


  }



  public function metas(){
      return $this->hasMany(TaskMeta::class);
  }

}
