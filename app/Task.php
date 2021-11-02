<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Task extends Model
{
  
  private $inn = [];

  //Errors
  private $error = null;
  private function refreshError(){
    $this->error = null;
  }  
  public function getError(){
    return $this->error;
  }
  //Metas
  private $metas = [];
  private function getMetas(){return $this->metas;}
  private function setMetas($metas){$this->metas = $metas;}
  

  function __construct($metas) {
    $this->setMetas($metas);
  }

  public function run(){
    
    $metas = $this->getMetas();

    //Get Inn
    if(!isset($metas['inn'])){
      $this->error = 'no Inn';
      return 0;
    }
    $inn = $metas['inn'];

    $inn = 780604379335; // @@@ todo

    //Parse
    $parse = $this->parse($inn);
    if(!$parse) return 0;

    $inns = $this->decode($parse);

    return $inns;

  }


  public function saveData($data){


  }


  public static function clean($str){
    return str_replace(["\r", "\n", "\t"],'',$str);
  }

  public function metas(){
      return $this->hasMany(TaskMeta::class);
  }

}
