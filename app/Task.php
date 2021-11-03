<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Task extends Model
{

  
  function __construct($metas = false) {
    if(is_array($metas) && isset($metas['inn'])){
      $this->setInn($metas['inn']);
    }
    if(is_int($metas)){
      $this->setInn($metas);
    }
    if(is_string($metas)){
      $this->setInn($metas);
    }
  }
  
  private $inn;
  public function getInn(){return $this->inn;}
  private function setInn($inn){$this->inn = $inn;}

  //Errors
  private $error = null;
  private function refreshError(){
    $this->error = null;
  }  
  public function getError(){
    return $this->error;
  }
  public function setError($err ){
    $this->error = $err;
    return false;
  }
  //Metas
  private $metas = [];
  private function getMetas(){return $this->metas;}
  private function setMetas($metas){$this->metas = $metas;}
  


  public function run(){
    
    $metas = $this->getMetas();

    //Get Inn
    $inn = $this->getInn();
    if(!$inn){
      $this->setError('no inn');
      return 0;
    }
    
    {//Parse
      $this->refreshError();
      $parse = $this->parse($inn);
      if(!$parse || $parse == ""){
        if($this->getError() == null) $this->setError('no parse');
        return 0;
      }
    }
        
    {//Decode
      $this->refreshError();
      $data = $this->decode($parse);
      if(!$data || $data == ""){
        if($this->getError() == null) $this->setError('no Decode data');
        return 0;
      }
    }

    {//Save
      $this->refreshError();
      $save = $this->saveData($data);
      if(!$save){
        if($this->getError() == null) $this->setError('save data error');
        return 0;
      }
    }

    return true;

  }


  public function saveData($data){
    return true;
  }


  public static function clean($str){
    $str = str_replace(["\r", "\n", "\t"],' ',$str);    
    return str_replace("   ",' ',$str);
  }

  public function metas(){
    return $this->morphMany(Meta::class, 'metable');
  }  

}
